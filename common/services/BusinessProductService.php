<?php
/**
 * 电商商品服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/9/17 17:23
 */

namespace common\services;

use common\helpers\ApcuHelper;
use common\helpers\ToolsHelper;
use common\models\BusinessProductList;
use common\models\BusinessProductRecord;
use common\models\elasticsearch\EsBusinessProduct;
use common\models\ProductCategoryHot;
use Yii;
use yii\helpers\ArrayHelper;

class BusinessProductService
{
    const STATUS_AUDIT = 0; // 无效
    const STAUTS_PASS = 1; // 有效

    const VIEW_RECORD_TYPE = 1; // 浏览记录
    const SHOPCAR_RECORD_TYPE = 2; // 加入购物车

    /**
     * 个性化推荐电商商品
     *
     * @param       $uid
     * @param array $params
     * @param       $page
     * @param       $pageSize
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/1 18:06
     */
    public function getProductByTuijain($uid, $params = [], $page, $pageSize)
    {
        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ],
                    ]
                ]
            ],
            'sort' => [
                'price' => [
                    'order' => "asc"
                ]
            ]
        ];


        if (!empty($params['keyword'])) { // 有搜索词时
            $search['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['keyword'],
                    'fields' => ["title", "category_name"]
                ]
            ];
        } else { // 个性推荐
            $favouriteKeywordArr = [];
            if (empty($params['type'])) { // 首页
                if (!empty($uid)) { // 用户个性推荐
                    $userService = new UserService();
                    list($favouriteCategoryArr, $favouriteKeywordArr) = $userService->getUserFavourite($uid);
                } else { // 游客
                    // todo 增加apcu
                    $favouriteCategoryArr = ProductCategoryHot::find()->select(['category_id'])->where(['status' => self::STAUTS_PASS, 'category_level' => 3])->column();
                }
            } else {
                if ($params['type'] == 3) { // 宠物领养
                    $favouriteCategoryArr = [1228, 1253, 1279, 1289, 1292, 1293];
                } else {
                    if ($params['type'] == 4) { // 房产出租
                        // todo 增加apcu
                        $favouriteCategoryArr = ProductCategoryHot::find()->select(['category_id'])->where(['status' => self::STAUTS_PASS, 'category_level' => 3])->column();
                    } else { // 热门专区、免费专区
                        if (!empty($uid)) { // 用户个性推荐
                            $userService = new UserService();
                            list($favouriteCategoryArr, $favouriteKeywordArr) = $userService->getUserFavourite($uid);
                        } else { // 游客
                            // todo 增加apcu
                            $favouriteCategoryArr = ProductCategoryHot::find()->select(['category_id'])->where(['status' => self::STAUTS_PASS, 'category_level' => 3])->column();
                        }
                    }
                }
            }


            if (!empty($favouriteKeywordArr)) {
                $search['query']['bool']['should'][] = [
                    'multi_match' => [
                        'query' => implode(',', $favouriteKeywordArr),
                        'fields' => ["title", "category_name"]
                    ]
                ];
                $search['query']['bool']['minimum_should_match'] = 1;
            }

            if (!empty($favouriteCategoryArr)) {
                $search['query']['bool']['should'][] = [
                    'terms' => [
                        'category_id' => $favouriteCategoryArr
                    ]
                ];
                $search['query']['bool']['minimum_should_match'] = 1;
            }
        }

        if (!empty($params['type'])) {
            if ($params['type'] == 1) { // 热门专区
                $search['sort'] = [
                    'click_num' => [
                        'order' => "desc"
                    ]
                ];
            } elseif ($params['type'] == 2) { // 免费专区
                $search['sort'] = [
                    'price' => [
                        'order' => "asc"
                    ],
                ];
            }
        }

        $resultData = EsBusinessProduct::search($search, $page, $pageSize);
        return $this->formatDataListFromEs($resultData["hits"]["hits"]);
    }


    /**
     * 同/不同分类电商商品推荐
     *
     * @param       $categoryIdArr
     * @param       $page
     * @param       $pageSize
     * @param       $reverse
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/1 18:06
     */
    public function getProductByCategory($categoryIdArr, $page, $pageSize, $reverse = 0)
    {
        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ],
                        ],
                    ],
                    'must' => [
                        [
                            'terms' => [
                                'category_id' => $categoryIdArr
                            ],
                        ],
                    ],
                ]
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

        $search['sort'] = [
            'price' => [
                'order' => "asc"
            ],
        ];

        $resultData = EsBusinessProduct::search($search, $page, $pageSize);
        return $this->formatDataListFromEs($resultData["hits"]["hits"]);
    }


    /**
     * 格式化数据
     *
     * @param array $list
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/17 17:42
     */
    private function formatDataListFromEs($list = [])
    {
        $businessProductSource = Yii::$app->params['businessProductSource'];
        $productList = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $item = $value['_source'];
                $picArr = json_decode($item['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $coverUrl = $picArr[0];

                $url = !empty($item['discount_url']) ? $item['discount_url'] : $item['url'];

                $productInfo = [];
                $productInfo['id'] = $item['id'];
                $productInfo['title'] = $item['title'];
                $productInfo['url'] = "/pages/union/proxy/proxy?spreadUrl=" . urlencode($url) . "&EA_PTAG=2018521330";
                $productInfo['category_id'] = $item['category_id'];
                $productInfo['tagList'] = json_decode($item['tags'], true);
                $productInfo['cover'] = $coverUrl;
                $productInfo['cover_height'] = 345;
                $productInfo['price'] = $item['price'];
                $productInfo['cash_back_price'] = $item['cash_back_price'];
                $productInfo['click_num'] = $item['click_num'];
                $productInfo['comment_num'] = $item['comment_num'] < 10000 ? $item['comment_num'] : round($item['comment_num'] / 10000, 1) . "万";
                $productInfo['sale_num'] = $item['sale_num'] < 10000 ? $item['sale_num'] : round($item['sale_num'] / 10000, 1) . "万";
                $productInfo['source_id'] = $item['source_id'];
                $productInfo['source_name'] = isset($businessProductSource[$item['source_id']]) ? $businessProductSource[$item['source_id']] : '';
                $productInfo['app_id'] = "wx91d27dbf599dff74";

                $productList[] = $productInfo;
            }
        }

        return $productList;
    }

    /**
     * 同类型商品推荐
     *
     * @param     $categoryId
     * @param int $limit
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/22 11:19
     */
    public function getTuijianBusinessProductList($categoryId, $limit = 50)
    {
        // 优先从同分类中查询
        $productList = [];
        if (!empty($categoryId)) {
            $productList = $this->getProductByCategory([$categoryId], 1, $limit);
        }
        $count = count($productList);

        if ($count < $limit) { // 如果条数不足，则从其他分类中取出剩余条数
            $otherProductList = $this->getProductByCategory([$categoryId], 1, 200, 1);
            $otherCount = count($otherProductList);

            if ($otherCount > 0) {
                // 随机取剩余几个
                $otherLimit = $limit - $count > $otherCount ? $otherCount : $limit - $count;
                if ($otherLimit == 1) { //array_rand当只随机一个时，array_rand会返回一个整数
                    $otherLimit = 4;
                }
                $otherProductArr = array_rand($otherProductList, $otherLimit);
                if (!empty($otherProductArr)) {
                    foreach ($otherProductArr as $id) {
                        $productList[] = $otherProductList[$id];
                    }
                }
            }
        }
        // 打乱顺序
        shuffle($productList);
        return array_values($productList);
    }


    /**
     * 通过API搜索电商商品
     *
     * @param int    $sourceId
     * @param string $keyword
     * @param int    $page
     * @param int    $pageSize
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/10 20:42
     */
    public function getBusinessProductByKeyword($sourceId = 1, $keyword = '', $page = 1, $pageSize = 30)
    {
        // 增加apcu缓存
        $apcuKey = ApcuHelper::RK("businessProductList", $sourceId, $keyword, $page);
        $productList = apcu_fetch($apcuKey, $exist);
        if (!$exist) {
            if ($sourceId == 1) {
                $jdService = new JdService();
                $productList = $jdService->getProductListByKeyword($keyword, $page, $pageSize);
            } else {
                $pddService = new PddService();
                $productList = $pddService->getProductListByKeyword($keyword, $page, $pageSize);
            }
            if (!empty($productList)) {
                apcu_store($apcuKey, $productList, 86400 * 3 + rand(0, 86400 * 2));
            }
        }

        return $this->formatProductList($productList);
    }

    /**
     * 通过API电商商品推荐
     *
     * @param int   $sourceId
     * @param int   $page
     * @param int   $pageSize
     * @param int   $channelType
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 19:08
     */
    public function getBusinessProductByRecommend($sourceId = 1, $page = 1, $pageSize = 30, $channelType = 1)
    {
        // 增加apcu缓存
        $apcuKey = ApcuHelper::RK("businessRecommendProductList", $sourceId, $channelType, $page);
        $productList = apcu_fetch($apcuKey, $exist);
        if (!$exist) {
            if ($sourceId == JdService::SOURCE_ID) {
                $jdService = new JdService();
                $productList = $jdService->getRecommendProductList($channelType, $page, $pageSize);
            } elseif ($sourceId == PddService::SOURCE_ID) {
                $pddService = new PddService();
                $productList = $pddService->getRecommendProductList($channelType, $page, $pageSize);
            }
            if (!empty($productList)) {
                apcu_store($apcuKey, $productList, 86400);
            }
        }

        return $this->formatProductList($productList);
    }

    /**
     * 记录用户行为日志
     *
     * @param        $uid
     * @param        $sourceId
     * @param        $businessProductId
     * @param string $productName
     * @param int    $type
     *
     * @author     xudt
     * @date-time  2021/10/16 22:21
     */
    public function record($uid, $businessProductId, $sourceId, $productName = "", $type = self::VIEW_RECORD_TYPE)
    {
        $businessProductRecordModel = BusinessProductRecord::find()->where(['uid' => $uid, 'business_product_id' => $businessProductId, 'type' => $type])->one();
        if (empty($businessProductRecordModel)) {
            $businessProductRecordModel = new BusinessProductRecord();
            $businessProductRecordModel->uid = $uid;
            $businessProductRecordModel->type = $type;
            $businessProductRecordModel->business_product_id = $businessProductId;
            $businessProductRecordModel->source_id = $sourceId;
            $businessProductRecordModel->product_name = $productName;
        }
        $businessProductRecordModel->updated_at = time();
        $businessProductRecordModel->save();
    }

    /**
     * 格式化电商数据
     *
     * @param $productList
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/10/14 17:41
     */
    private function formatProductList($productList)
    {
        $businessProductSource = Yii::$app->params['businessProductSource'];
        if (!empty($productList)) {
            foreach ($productList as $key => &$value) {
                $picArr = json_decode($value['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }

                $tagList = [];
                $tagArr = json_decode($value['tags'], true);
                foreach ($tagArr as $k => $tag) {
                    if ($k >= 2) {
                        continue;
                    }
                    if ($value['source_id'] == 2) { // 拼多多标签简化
                        $tag = ToolsHelper::pddTagShort($tag);
                        if (empty($tag) || in_array($tag, $tagList)) {
                            continue;
                        }
                    }
                    $tagList[] = $tag;
                }

                $value['return_price'] = ToolsHelper::getCashBackPrice($value['cash_back_price']);
                $value['cover'] = $picArr[0];
                $value['cover_height'] = 345;
                $value['tagList'] = $tagList;
                if ($value['source_id'] == 1) {
                    $value['click_url'] = "/pages/union/proxy/proxy?spreadUrl=" . urlencode($value['click_url']) . "&EA_PTAG=" . JdService::JD_ID;
                }
                $value['source_name'] = isset($businessProductSource[$value['source_id']]) ? $businessProductSource[$value['source_id']] : '';
            }
        }
        return $productList;
    }


    /**
     * 用户行为日志列表
     *
     * @param $uid
     * @param $type 1 浏览记录 2 加入购物车
     * @param $keyword
     * @param $page
     * @param $pageSize
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/10/14 17:56
     */
    public function getRecordListByType($uid, $type = 1, $keyword = '', $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        $businessProductArr = BusinessProductRecord::find()->select(['updated_at'])->where(['uid' => $uid, 'type' => $type])->andFilterWhere(['LIKE', 'product_name', $keyword])->offset($start)->limit($pageSize)->orderBy('updated_at desc')->indexBy('business_product_id')->column();
        $businessProductIdArr = $productList = [];
        if (!empty($businessProductArr)) {
            foreach ($businessProductArr as $id => $updatedAt) {
                $businessProductIdArr[] = $id;
            }
        }
        if (!empty($businessProductArr)) {
            $productList = BusinessProductList::find()->where(['business_product_id' => $businessProductIdArr])->indexBy('business_product_id')->asArray()->all();
        }
        if (!empty($productList)) {
            foreach ($productList as $key => &$value) {
                $businessProductId = $value['business_product_id'];
                $value['updated_at'] = isset($businessProductArr[$businessProductId]) ? $businessProductArr[$businessProductId] : 0;
            }
            ArrayHelper::multisort($productList, 'updated_at', SORT_DESC);
            return $this->formatProductList($productList);
        }
        return [];
    }

    /**
     * 用户行为数据
     *
     * @param $uid
     * @param $type
     *
     * @return bool|int|string|null
     *
     * @author     xudt
     * @date-time  2021/10/16 21:53
     */
    public static function getUserBehaviorData($uid, $type)
    {
        return BusinessProductRecord::find()->where(['uid' => $uid, 'type' => $type])->count();
    }

    /**
     * 移除
     *
     * @param $uid
     * @param $businessProductId
     * @param $sourceId
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/17 20:45
     */
    public function removeShopcar($uid, $businessProductId, $sourceId)
    {
        return BusinessProductRecord::deleteAll(['uid' => $uid, 'business_product_id' => $businessProductId, 'source_id' => $sourceId, 'type' => self::SHOPCAR_RECORD_TYPE]);
    }

}