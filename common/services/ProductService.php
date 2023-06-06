<?php
/**
 * 商品服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/19 10:15
 */

namespace common\services;

use common\helpers\ApcuHelper;
use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Clock;
use common\models\elasticsearch\EsProduct;
use common\models\Product;
use common\models\ProductCategory;
use common\models\ProductCategoryHot;
use common\models\Stick;
use common\models\User;
use common\models\UserData;
use console\services\jobs\MessageJob;
use Yii;

class ProductService
{
    const STATUS_AUDIT = 0; // 待审核
    const STAUTS_PASS = 1; // 通过
    const STATUS_REFUSE = 2; // 不通过
    const STATUS_DOWN = 3; // 下架
    const STAUTS_SALE = 4; // 已卖出
    const STATUS_DEL = 5; // 删除

    const UID_OFFSET = 100000; // 为了bitmap从0开始；

    /**
     * 从数据库中读取商品信息
     *
     * @param int $uid
     * @param int $productId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 10:38
     */
    public function getProductInfoFromDb($uid, $productId)
    {
        $productInfo = Product::find()->where(['id' => $productId])->asArray()->one();
        if (empty($productInfo)) {
            return ToolsHelper::funcReturn("商品不存在");
        }

        if ($productInfo['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $productInfo['pics'] = !empty($productInfo['pics']) ? json_decode($productInfo['pics'], true) : [];
        if (!empty($productInfo['pics'])) {
            foreach ($productInfo['pics'] as $k => $picUrl) {
                $productInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl);
            }
        }

        // 是否为热门
        $productInfo['is_hot'] = $productInfo['view_num'] >= 200 ? 1 : 0;

        $productInfo['tags'] = !empty($productInfo['tags']) ? json_decode($productInfo['tags'], true) : [];
        $productInfo['info'] = preg_replace("/<p\/>/", "\n", $productInfo['info']);

        return ToolsHelper::funcReturn("商品信息", true, ['productInfo' => $productInfo]);
    }

    /**
     * 保存商品信息
     *
     * @param $userInfo
     * @param $productInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function saveProductInfoToDb($userInfo, $productInfo)
    {
        // 是否关注公众号
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);

        $now = time();
        $productInfo['info'] = preg_replace("/[\n\r]/", "<p/>", $productInfo['info']);
        $productInfo['pics'] = str_replace(Yii::$app->params['assetDomain'], "", $productInfo['pics']);

        if ($productInfo['id'] == 0) {
            $productModel = new Product();
            $productInfo['uid'] = $userInfo['uid'];
            $productInfo['created_at'] = $now;
        } else {
            $productModel = Product::find()->where(['id' => $productInfo['id']])->one();
            if ($productModel->uid != $userInfo['uid']) {
                return ToolsHelper::funcReturn("非法操作");
            }
        }
        $productInfo['status'] = self::STATUS_AUDIT;
        $productInfo['updated_at'] = $now;
        $productModel->attributes = $productInfo;
        if ($productModel->save()) {
            // 判断ES的状态是否为审核通过,若为审核通过则不需要审核；
            $productEsData = EsProduct::get($productModel->id);
            $userLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
            if ($userLevel >= Yii::$app->params['vipPrivillegeArr'][2]['min_level'] && !empty($productEsData) && $productEsData['status'] == ProductService::STAUTS_PASS) {
                $productInfo['uid'] = $productModel->uid;
                return $this->autoPass($productInfo, $productEsData, $subscribe);
            }
            if (empty($productEsData)) { // 新增
                $rewardPoint = Yii::$app->params['awardType'][12]['point'];
                return ToolsHelper::funcReturn("发布成功，审核通过后将获得" . $rewardPoint . "积分", true);
            }
            return ToolsHelper::funcReturn("发布成功，两小时内完成审核", true);
        }
        Yii::info(['userInfo' => $userInfo, 'productInfo' => $productInfo, 'subscribe' => $subscribe, 'now' => $now, 'reason' => $productModel->getErrors()], 'trace');
        return ToolsHelper::funcReturn("发布失败");
    }

    /**
     * 自动审核通过
     *
     * @param array   $productInfo
     * @param array   $productEsData
     * @param integer $subscribe
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/15 21:37
     */
    private function autoPass($productInfo = [], $productEsData = [], $subscribe = 0)
    {
        $now = time();
        $productModel = Product::find()->where(['id' => $productInfo['id']])->one();
        if (empty($productModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }

        // 检查用户的微信是否绑定，若未绑定，直接审核不通过
        $userInfo = User::find()->where(['id' => $productInfo['uid']])->asArray()->one();
        if (($userInfo['wx'] == "" || $userInfo['wx_public'] == 0) && $subscribe == 0) {
            return ToolsHelper::funcReturn("请绑定微信号并同意对方复制或关联公众号");
        }

        // 修改数据库
        $productModel->status = self::STAUTS_PASS;
        $productModel->audit_at = $now;
        $productModel->updated_at = $now;
        if ($productModel->save()) {
            //写入geoRedis
            $this->addProductGeoData($productInfo['id'], $productInfo['lat'], $productInfo['lng']);

            // 更新到ES
            unset($productInfo['updated_at']); // 修改不更新ES里的更新时间
            $productInfo['status'] = self::STAUTS_PASS;
            $productInfo['audit_at'] = $now; // 为了避免不修改进行保存不报错
            $productInfo['price'] = floatval($productInfo['price']);
            $productInfo['cut_price'] = intval($productEsData['price'] - $productInfo['price']);
            if (EsProduct::update($productInfo['id'], $productInfo)) {
                // 增加自动审核日志
                Yii::info(['productInfo' => $productInfo], "autoPass");
                return ToolsHelper::funcReturn("会员特权，修改无需审核", true);
            }
        }
        return ToolsHelper::funcReturn("发布失败，请联系客服");
    }

    /**
     * 添加商品的位置信息到geoRedis中
     *
     * @param $productId
     * @param $lat
     * @param $lng
     *
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:37
     */
    public function addProductGeoData($productId, $lat, $lng)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeo');

        $redisClient->geoadd($redisKey, $lng, $lat, $productId);
    }

    /**
     * 从geoRedis中读取附近的商品列表
     *
     * @param $lat
     * @param $lng
     * @param $distType
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/3/1 16:44
     */
    private function getProductListFromGeoRedis($lat, $lng, $distType)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeo');
        return $redisClient->georadius($redisKey, floatval($lng), floatval($lat), $distType, 'km', 'WITHDIST');
    }

    /**
     * 刷新商品更新时间
     *
     * @param $userInfo
     * @param $productId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 13:25
     */
    public function refreshProduct($userInfo, $productId)
    {
        $now = time();
        if (empty($productId)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $productModel = Product::find()->where(['id' => $productId])->one();
        if ($productModel->uid != $userInfo['uid']) {
            return ToolsHelper::funcReturn("非法操作");
        }
        if ($productModel->status != self::STAUTS_PASS) {
            return ToolsHelper::funcReturn("非上架商品无法刷新");
        }

        // 判断用户今日是否已擦亮过
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('onceOfDateRefresh', date('Ymd', $now));
        if ($redisBaseCluster->getBit($redisKey, $userInfo['uid'] - self::UID_OFFSET)) {
            return ToolsHelper::funcReturn("您今天已经擦亮过了");
        }

        // 读取redis查看用户当前积分
        $userService = new UserService();
        $rewardPoint = $userService->getUserStructFromRedis($userInfo['uid'], 'reward_point');

        $point = Yii::$app->params['awardType'][RewardPointService::REFRESH_AWARD_TYPE]['point'];
        if ($rewardPoint < $point) {
            return ToolsHelper::funcReturn("积分不足，无法完成本次操作");
        }

        $productModel->updated_at = $now;
        if ($productModel->save()) {
            if (EsProduct::update($productId, ['updated_at' => $now])) {
                $redisBaseCluster->setBit($redisKey, $userInfo['uid'] - self::UID_OFFSET, 1);
                return ToolsHelper::funcReturn("擦亮成功", true);
            }
        }
        return ToolsHelper::funcReturn("擦亮失败");
    }

    /**
     * 更新商品状态
     *
     * @param $userInfo
     * @param $productId
     * @param $opType
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:49
     */
    public function statusProduct($userInfo, $productId, $opType)
    {
        $now = time();
        if (empty($productId) || empty($opType)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productModel = Product::find()->where(['id' => $productId])->one();
        if ($productModel->uid != $userInfo['uid']) {
            return ToolsHelper::funcReturn("非法操作");
        }

        switch ($opType) {
            case 'del':
                $status = self::STATUS_DEL;
                break;
            case 'down':
                $status = self::STATUS_DOWN;
                break;
            case 'up':
                $status = self::STAUTS_PASS;
                break;
            case 'sale':
                $status = self::STAUTS_SALE;
                break;
            default:
                $status = 0;
        }
        if ($status == 0) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $productModel->status = $status;
        $productModel->updated_at = $now;
        if ($productModel->save()) {
            $productEsData = EsProduct::get($productId);
            if (!empty($productEsData)) {
                if (EsProduct::update($productId, ['status' => $status])) {
                    /** @var \redisCluster $redisBaseCluster */
                    $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
                    $redisKey = RedisHelper::RK('userProductData', 'sale', $userInfo['uid']);
                    if ($opType == 'sale') { //卖出时写入redis
                        $redisBaseCluster->zAdd($redisKey, $now, $productId);

                        // 写入到跑马灯Redis数据中
                        $noticeRedisKey = RedisHelper::RK('userAwardPointRecord');
                        $redisBaseCluster->zAdd(
                            $noticeRedisKey,
                            $now,
                            json_encode(
                                [
                                    'type' => 200, // 已卖出
                                    'title' => $productModel->name,
                                    'uid' => $userInfo['uid'],
                                    'created_at' => $now,
                                ]
                            )
                        );
                    } elseif ($opType == 'up') { // 重新上架时删除redis成员
                        $redisBaseCluster->zRem($redisKey, $productId);
                    }
                }
            }
            return ToolsHelper::funcReturn("操作成功", true, ['status' => $status]);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 我发布的/我卖出的总数
     *
     * @param     $uid
     * @param int $status
     *
     * @author     xudt
     * @date-time  2021/2/26 20:18
     */
    public function getProductNum($uid, $status = -1)
    {
        if ($status == -1) {
            return Product::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL])->count();
        } else {
            return Product::find()->where(['uid' => $uid, 'status' => $status])->count();
        }
    }


    /**
     * 获取用户我发布的
     *
     * @param     $uid
     * @param     $page
     * @param     $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/3/11 11:12
     */
    public function getProductList($uid, $page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $productModel = Product::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL]);
        $productList = $productModel->offset($start)->limit($pageSize)->orderBy('status asc,updated_at desc')->asArray()->all();
        if (!empty($productList)) {
            foreach ($productList as $key => &$value) {
                $value['audit_date'] = !empty($value['audit_at']) ? date("Y-m-d H:i:s", $value['audit_at']) : '';
                $picArr = json_decode($value['pics'], true);
                $value['pics'] = $picArr;
                $value['cover'] = isset($picArr[0]) ? ToolsHelper::getLocalImg($picArr[0]) : '';
                //商品数据
                $productData = $this->getProductData($value['id']);
                isset($productData['view_num']) && $value['view_num'] = $productData['view_num'];
                isset($productData['thumb_num']) && $value['thumb_num'] = $productData['thumb_num'];
                isset($productData['comment_num']) && $value['comment_num'] = $productData['comment_num'];
                isset($productData['want_num']) && $value['want_num'] = $productData['want_num'];
            }
        }
        return $productList;
    }

    /**
     * 获取在售商品列表
     *
     * @param $authorData
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/3/12 09:38
     */
    public function getSalingProductList($authorData, $page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $productList = Product::find()->select(['id', 'title', 'pics', 'price'])->where(['uid' => $authorData['uid']])->andWhere(['status' => self::STAUTS_PASS])->offset($start)->limit($pageSize)->orderBy('updated_at desc')->asArray()->all();
        if (!empty($productList)) {
            foreach ($productList as $key => &$value) {
                $picArr = json_decode($value['pics'], true);
                $value['cover'] = isset($picArr[0]) ? ToolsHelper::getLocalImg($picArr[0]) : '';
                $value['nickname'] = $authorData['nickname'];
                $value['avatar'] = $authorData['avatar'];
                $value['status'] = self::STAUTS_PASS;
                $value['want_num'] = $this->getProductData($value['id'], 'want_num');
            }
        }
        return $productList;
    }


    /**
     * 获取用户卖出、浏览、点赞、评论、想要商品数量
     *
     * @param $uid
     * @param $type
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/2/26 20:36
     */
    public function getUserActiveData($uid, $type)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("userProductData", $type, $uid);
        return $redisBaseCluster->zCard($redisKey);
    }

    /**
     * 获取用户卖出、浏览、点赞、评论、想要商品列表
     *
     * @param $uid
     * @param $type
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/2/26 21:33
     */
    public function getUserActiveProductList($uid, $type, $page, $pageSize)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("userProductData", $type, $uid);
        $start = ($page - 1) * $pageSize;
        $end = $start + ($pageSize - 1);
        $redisDataList = $redisBaseCluster->zRevRange($redisKey, $start, $end, true);
        if (empty($redisDataList)) {
            return [];
        }
        $productIdArr = array_keys($redisDataList);

        // 读取ES
        $search = [
            'query' => [
                'terms' => [
                    'id' => $productIdArr
                ],
            ]
        ];
        $resultData = EsProduct::search($search, 1, $pageSize);
        return $this->formatDataListFromEs($resultData["hits"]["hits"], $redisDataList);
    }

    /**
     * 从Es格式化输出商品列表
     *
     * @param array $list
     * @param array $redisDataList
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/13 18:37
     */
    private function formatDataListFromEs($list = [], $redisDataList = [])
    {
        $productList = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $item = $value['_source'];
                $picArr = json_decode($item['pics'], true);
                $coverPathUrl = "";
                foreach ($picArr as $k => $imgUrl) {
                    if ($k == 0) {
                        $coverPathUrl = $imgUrl;
                    }
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $coverUrl = $picArr[0];
                $coverHeight = ToolsHelper::getCoverHeight($coverPathUrl);

                $productInfo = [];
                $productInfo['id'] = $item['id'];
                $productInfo['uid'] = $item['uid'];
                $productInfo['nickname'] = $item['nickname'];
                $productInfo['avatar'] = ToolsHelper::getLocalImg($item['avatar'], '', 240);
                $productInfo['title'] = $item['title'];
                $productInfo['cover'] = $coverUrl;
                $productInfo['cover_height'] = $coverHeight;
                $productInfo['price'] = $item['price'];
                $productInfo['want_num'] = $this->getProductData($item['id'], 'want_num');
                $productInfo['status'] = $item['status'];
                $productList[$item['id']] = $productInfo;
            }
        }

        $dataList = [];
        //按加入时间重新排序
        if (!empty($redisDataList) && !empty($productList)) {
            foreach ($redisDataList as $id => $joinAt) {
                if (isset($productList[$id])) {
                    $dataList[] = $productList[$id];
                }
            }
        } else {
            $dataList = $productList;
        }

        return $dataList;
    }

    /**
     * 移除用户想要商品列表或移除下架商品
     *
     * @param $uid
     * @param $productId
     * @param $type
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/2/28 19:32
     */
    public function activeProductRemove($uid, $productId, $type)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("userProductData", $type, $uid);
        $res = $redisBaseCluster->zRem($redisKey, $productId);
        if ($res) {
            return ToolsHelper::funcReturn("移除成功", true);
        }
        return ToolsHelper::funcReturn("移除失败");
    }

    /**
     * 根据商品id获取商品列表
     *
     * @param int   $uid
     * @param array $productIdArr
     * @param int   $isZhiding
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/12 15:36
     */
    public function getProductListByIds($uid = 0, $productIdArr = [], $isZhiding = 0)
    {
        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'terms' => [
                                'id' => $productIdArr
                            ]
                        ],
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ]
                    ],
                ]
            ],
            'sort' => [
                'updated_at' => [
                    'order' => "desc"
                ]
            ]
        ];

        $resultData = EsProduct::search($search, 1, 20);
        return $this->formatIndexDataListFromEs($uid, $resultData["hits"]["hits"], [], $isZhiding);
    }


    /**
     * 按距离查找商品列表
     *
     * @param       $uid
     * @param array $params
     * @param       $page
     * @param       $pageSize
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 20:25
     */
    public function getIndexProductList($uid, $params = [], $page, $pageSize)
    {
        $productArr = [];
        if (!empty($params['lat'])) {
            // 当获取到定位时，若选择全部，则默认为60km范围内
            $distType = !empty($params['dist_type']) ? $params['dist_type'] : 60;
            $distList = $this->getProductListFromGeoRedis($params['lat'], $params['lng'], $distType);
            if (!empty($distList)) {
                foreach ($distList as $key => $value) {
                    $productArr[$value[0]] = $value[1] * 1000;
                }
            }
            if (empty($productArr)) {
                return [];
            }
        }


        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ]
                    ],
                ]
            ],
            'sort' => [
                'updated_at' => [
                    'order' => "desc"
                ]
            ]
        ];

        if (!empty($productArr)) {
            $search['query']['bool']['must'][] = [
                'terms' => [
                    'id' => array_keys($productArr)
                ]
            ];
        }


        if (!empty($params['keyword'])) {
            $search['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['keyword'],
                    'fields' => ["name", "title", "desc", "category_name"]
                ]
            ];
        }

        // 宠物、房产分类
        $chongwuCategoryIdArr = [1228, 1253, 1279, 1289, 1292, 1293];
        $fangchanCategoryIdArr = [1796];

        if (!empty($params['type'])) {
            switch ($params['type']) {
                case 1: // 热门专区
                    $search['query']['bool']['must'][] = [
                        'range' => [
                            'view_num' => [
                                'gte' => 100
                            ]
                        ]
                    ];
                    break;
                case 2: // 免费专区
                    $search['query']['bool']['must'][] = [
                        'match' => [
                            'price' => 0.00
                        ]
                    ];


                    $search['query']['bool']['must_not'][] = [
                        'terms' => [
                            'category_id' => $chongwuCategoryIdArr
                        ]
                    ];
                    break;
                case 3: // 宠物领养
                    $search['query']['bool']['must'][] = [
                        'terms' => [
                            'category_id' => $chongwuCategoryIdArr
                        ]
                    ];
                    break;
                case 4: // 房产租房
                    $search['query']['bool']['must'][] = [
                        'terms' => [
                            'category_id' => $fangchanCategoryIdArr
                        ]
                    ];
                    break;
            }
        }


        // 屏蔽用户
        $userService = new UserService();
        $denyUserArr = $userService->getDenyUserArr();
        if (!empty($denyUserArr)) {
            $search['query']['bool']['must_not'][] = [
                'terms' => [
                    'uid' => $denyUserArr
                ]
            ];
        }
        $resultData = EsProduct::search($search, $page, $pageSize);
        return $this->formatIndexDataListFromEs($uid, $resultData["hits"]["hits"], $productArr);
    }

    /**
     * 按分类查询商品列表
     *
     * @param $categoryIdArr
     * @param $page
     * @param $params
     * @param $pageSize
     * @param $reverse //是否反向，1表示不包含
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/16 16:58
     */
    public function getCategoryProductList($categoryIdArr, $params = [], $page, $pageSize, $reverse = 0)
    {
        $productArr = [];
        if (!empty($params['dist_type'])) {
            $distList = $this->getProductListFromGeoRedis($params['lat'], $params['lng'], $params['dist_type']);
            if (!empty($distList)) {
                foreach ($distList as $key => $value) {
                    $productArr[$value[0]] = $value[1] * 1000;
                }
            }
            if (empty($productArr)) {
                return [];
            }
        }


        $search['query'] = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'category_id' => $categoryIdArr
                        ],
                    ],
                    [
                        'term' => [
                            'status' => self::STAUTS_PASS
                        ]
                    ]
                ],
            ]
        ];

        if ($reverse == 1) { // 一定不包含该分类
            $search['query'] = [
                'bool' => [
                    'must_not' => [
                        [
                            'terms' => [
                                'category_id' => $categoryIdArr
                            ]
                        ]
                    ],
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ]
                    ]
                ]
            ];
        }

        // 当选择距离时且附近有物品时
        if (!empty($productArr)) {
            $search['query']['bool']['must'][] = [
                'terms' => [
                    'id' => array_keys($productArr)
                ]
            ];
        }

        $search['sort'] = [
            'updated_at' => [
                'order' => "desc"
            ]
        ];

        // 屏蔽用户
        $userService = new UserService();
        $denyUserArr = $userService->getDenyUserArr();
        if (!empty($denyUserArr)) {
            $search['query']['bool']['must_not'][] = [
                'terms' => [
                    'uid' => $denyUserArr
                ]
            ];
        }

        $resultData = EsProduct::search($search, $page, $pageSize);
        return $this->formatDataListFromEs($resultData["hits"]["hits"]);
    }


    /**
     * 从Es格式化输出商品列表
     *
     * @param int   $uid
     * @param array $data
     * @param array $productArr
     * @param int   $isZhiding
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/12 15:34
     */
    private function formatIndexDataListFromEs($uid = 0, $data = [], $productArr = [], $isZhiding = 0)
    {
        $productList = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $productInfo = $value['_source'];
                $productId = $productInfo['id'];
                $picArr = json_decode($productInfo['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $productInfo['tags'] = json_decode($productInfo['tags']);
                $productInfo['pics'] = $picArr;
                $dist = isset($productArr[$productId]) ? $productArr[$productId] : 0;
                switch (true) {
                    case $dist > 1000:
                        $productInfo['dist'] = round($dist / 1000, 1) . "km";
                        break;
                    case $dist <= 1000 && $dist > 200:
                        $productInfo['dist'] = intval($dist) . '米';
                        break;
                    case $dist <= 200 && $dist > 0:
                        $productInfo['dist'] = '200米以内';
                        break;
                    default:
                        $productInfo['dist'] = '';
                }
                //商品数据
                $productData = $this->getProductData($productId);
                isset($productData['view_num']) && $productInfo['view_num'] = $productData['view_num'];
                isset($productData['thumb_num']) && $productInfo['thumb_num'] = $productData['thumb_num'];
                isset($productData['comment_num']) && $productInfo['comment_num'] = $productData['comment_num'];
                isset($productData['want_num']) && $productInfo['want_num'] = $productData['want_num'];

                // 是否为热门
                $productInfo['is_hot'] = $productInfo['view_num'] >= 100 ? 1 : 0;
                if ($isZhiding) {
                    $productInfo['is_hot'] = 0;
                }
                $productInfo['is_zhiding'] = $isZhiding;

                // 是否点赞
                $productInfo['is_thumb_over'] = 0;
                if (!empty($uid)) {
                    $thumbService = new ThumbService($productId, $uid);
                    $productInfo['is_thumb_over'] = $thumbService->isThumbProductOver();
                }
                $productInfo['publish_time'] = ToolsHelper::getTimeStrDiffNow($productInfo['updated_at']);
                $productInfo['type'] = 'product';
                $productInfo['nickname'] = ToolsHelper::ellipsisStr($productInfo['nickname'], 12);
                unset($productInfo['name'], $productInfo['info'], $productInfo['status'], $productInfo['created_at']);
                unset($productInfo['audit_at'], $productInfo['audit_reason'], $productInfo['lat'], $productInfo['lng'], $productInfo['updated_at']);
                $productList[] = $productInfo;
            }
        }
        return $productList;
    }


    /**
     * 商品详情页面数据
     *
     * @param        $productId
     * @param int    $uid
     * @param string $from
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/9 10:03
     */
    public function getProductPageData($productId, $uid = 0, $from = '')
    {
        if ($from == 'mycenter') { //用户后台查看文章详情
            $productInfo = Product::find()->where(['id' => $productId])->asArray()->one();
        } else {
            $productInfo = EsProduct::get($productId);
        }
        if (empty($productInfo)) {
            return [];
        }

        $productInfo['pics'] = !empty($productInfo['pics']) ? json_decode($productInfo['pics'], true) : [];
        if (!empty($productInfo['pics'])) {
            foreach ($productInfo['pics'] as $k => $picUrl) {
                $productInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl, '', 540);
            }
        }

        $productInfo['info'] = str_replace("<p/>", "<br>", $productInfo['info']);
        $productInfo['tags'] = !empty($productInfo['tags']) ? json_decode($productInfo['tags'], true) : [];
        $productInfo['publish_time'] = $productInfo['status'] == self::STAUTS_PASS ? ToolsHelper::getTimeStrDiffNow($productInfo['updated_at']) : '';
        $productInfo['type'] = 'product';

        //商品数据
        $productData = $this->getProductData($productId);
        isset($productData['view_num']) && $productInfo['view_num'] = $productData['view_num'];
        isset($productData['thumb_num']) && $productInfo['thumb_num'] = $productData['thumb_num'];
        isset($productData['comment_num']) && $productInfo['comment_num'] = $productData['comment_num'];
        isset($productData['want_num']) && $productInfo['want_num'] = $productData['want_num'];

        // 是否为热门
        $productInfo['is_hot'] = $productInfo['view_num'] >= 100 ? 1 : 0;

        //用户统计数据
        $authorData = $this->getUserStatisticData($productInfo['uid']);

        // 获取用户活跃时间
        $userService = new UserService();
        $activeAt = $userService->getUserStructFromRedis($productInfo['uid'], 'active_at');
        $productInfo['active_time'] = intval($activeAt) > 0 ? ToolsHelper::getTimeStrDiffNow($activeAt) : '';

        // 是否点赞
        $thumbService = new ThumbService($productId, $uid);
        $productInfo['is_thumb_over'] = $thumbService->isThumbProductOver();

        return array_merge($productInfo, $authorData);
    }


    /**
     * 用户统计数据
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/11 11:03
     */
    public function getUserStatisticData($uid)
    {
        $authorData = User::find()->select(['nickname', 'avatar', 'gender', 'created_at'])->where(['id' => $uid])->asArray()->one();
        if (!empty($authorData)) {
            $rewardPoint = UserData::find()->select(['reward_point'])->where(['uid' => $uid])->scalar();
            $authorData['level'] = ToolsHelper::getUserLevel($rewardPoint);
            $authorData['avatar'] = ToolsHelper::getLocalImg($authorData['avatar'], '', 240);
            $authorData['join_day'] = ceil((time() - $authorData['created_at']) / 86400); //加入天数
            $authorData['publish_num'] = $this->getProductNum($uid); //发布商品数
            $authorData['saled_num'] = $this->getProductNum($uid, self::STAUTS_SALE); //卖出商品数
            $authorData['saling_num'] = $this->getProductNum($uid, self::STAUTS_PASS); //在售商品数
            $authorData['uid'] = $uid;
        }
        return $authorData;
    }

    /**
     * 获取商品数据
     *
     * @param        $productId
     * @param string $hashKey
     *
     * @return array|int
     *
     * @author     xudt
     * @date-time  2021/3/20 15:04
     */
    public function getProductData($productId, $hashKey = '')
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("productData", $productId);
        if (empty($hashKey)) {
            return $redisBaseCluster->hGetAll($redisKey);
        } else {
            return intval($redisBaseCluster->hGet($redisKey, $hashKey));
        }
    }


    /**
     * 获取商品详情
     *
     * @param $productId
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/15 18:00
     */
    public function getProductInfo($productId)
    {
        $productInfo = Product::find()->select(['id', 'uid', 'name', 'title', 'pics', 'price', 'original_price'])->where(['id' => $productId])->asArray()->one();
        if (empty($productInfo)) {
            return [];
        }
        $picArr = json_decode($productInfo['pics'], true);
        $productInfo['cover'] = ToolsHelper::getLocalImg($picArr[0], '', 240);
        unset($productInfo['pics']);
        return $productInfo;
    }

    /**
     * 增加商品浏览数据
     *
     * @param $productId
     * @param $uid
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/9 10:46
     */
    public function incrViewProductData($productId, $uid)
    {
        $now = time();
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('productData', $productId);
        $res = $redisBaseCluster->hIncrBy($redisKey, 'view_num', 1);
        if ($res && $uid != 0) {
            $redisKey = RedisHelper::RK('userProductData', 'view', $uid);
            $redisBaseCluster->zAdd($redisKey, $now, $productId);
        }
        return $res;
    }

    /**
     * 增加商品我想要数据
     *
     * @param $productId
     * @param $uid
     *
     * @author     xudt
     * @date-time  2021/3/15 09:45
     */
    public function incrWantProductData($productId, $uid)
    {
        $now = time();
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('userProductData', 'want', $uid);
        $res = $redisBaseCluster->zAdd($redisKey, $now, $productId);
        if ($res) {
            $redisKey = RedisHelper::RK('productData', $productId);
            $redisBaseCluster->hIncrBy($redisKey, 'want_num', 1);
        }
    }

    /**
     * 从geoRedis中读取附近的置顶商品列表
     *
     * @param array $params
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/6/21 14:09
     */
    public function getStickProductListFromGeoRedis($params = [])
    {
        $type = !empty($params['type']) ? $params['type'] : 0;
        $activityId = !empty($params['activity_id']) ? $params['activity_id'] : 0;
        if (!empty($params['lng']) && !empty($params['lat'])) {
            /** @var \Redis $redisClient */
            $redisClient = Yii::$app->get('redisGeo');
            $redisKey = RedisHelper::RK('distGeoStick', $type, $activityId);
            return $redisClient->georadius($redisKey, $params['lng'], $params['lat'], 10, 'km');
        }
        return [];
    }

    /**
     * 判断点我想要免费商品是否扣过积分
     *
     * @param $uid
     * @param $productId
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/6/30 17:22
     */
    public function isWantFreeAward($uid, $productId)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('wantFreeAward', $uid);
        return $redisBaseCluster->sIsMember($redisKey, $productId);
    }

    /**
     * 加入点我想要免费商品是否扣过积分
     *
     * @param $uid
     * @param $productId
     *
     * @return false|int
     *
     * @author     xudt
     * @date-time  2021/6/30 17:25
     */
    public function setWantFreeAward($uid, $productId)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('wantFreeAward', $uid);
        return $redisBaseCluster->sAdd($redisKey, $productId);
    }

    /**
     * 获取分类列表
     *
     * @param int $pid
     * @param int $hot
     * @param int $children
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/9/10 09:45
     */
    public function getCategoryByPid($pid = 0, $hot = 0, $children = 0)
    {
        // apcu 缓存
        $apcuKey = ApcuHelper::RK("categoryList", $pid, $hot, $children);
        $categoryList = apcu_fetch($apcuKey, $exist);
        if (!$exist) {
            if ($hot) {
                $model = ProductCategoryHot::find()->select(['category_id AS id', 'category_name', 'icon']);
            } else {
                $model = ProductCategory::find()->select(['id', 'category_name', 'icon']);
            }
            $categoryList = $model->where(['pid' => $pid, 'status' => 1])->orderBy('sort asc,id asc')->asArray()->all();
            foreach ($categoryList as $key => &$value) {
                $value['icon'] = !empty($value['icon']) ? ToolsHelper::getLocalImg($value['icon']) : '';
                if ($children) {
                    $value['children'] = $this->getCategoryByPid($value['id'], $hot);
                }
            }
            apcu_store($apcuKey, $categoryList, 3600);
        }
        return $categoryList;
    }

    /**
     * 根据关键词搜索到的分类
     *
     * @param $keyword
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/10/8 11:46
     */
    public function getSearchCategoryList($keyword)
    {
        $categoryList = ProductCategory::find()->where(['LIKE', 'category_name', $keyword])->andWhere(['category_level' => 3, 'status' => 1])->asArray()->all();
        if (!empty($categoryList)) {
            foreach ($categoryList as $key => &$value) {
                $value['icon'] = !empty($value['icon']) ? ToolsHelper::getLocalImg($value['icon']) : '';
            }
        }
        if (!empty($categoryList)) {
            return [
                [
                    'category_name' => '为您搜索到以下分类：',
                    'icon' => '',
                    'id' => 200001,
                    'children' => $categoryList,
                ]
            ];
        }
        return [];
    }
}