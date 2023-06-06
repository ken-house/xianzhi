<?php
/**
 * 后台商品服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:58
 */

namespace backend\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\elasticsearch\EsProduct;
use common\models\Product;
use common\models\ProductCategory;
use common\models\Stick;
use common\models\UnionOpenid;
use common\models\User;
use common\services\MessageService;
use common\services\OfficialAccountService;
use common\services\ProductService as CommonProductService;
use common\services\RewardPointService;
use common\services\TemplateMsgService;
use console\services\jobs\MessageJob;
use Yii;
use yii\helpers\ArrayHelper;

class ProductService
{
    /**
     * 获取商品列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 11:29
     */
    public function getProductList($data = [])
    {
        $productModel = Product::find()->andFilterWhere(['LIKE', 'name', $data['product_name']]);
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $productModel->andWhere(['>=', 'updated_at', $data['start_date']])->andWhere(['<=', 'updated_at', $data['end_date']]);
        }
        if (!empty($data['product_id'])) {
            $productModel->andWhere(['id' => $data['product_id']]);
        }
        if (!empty($data['uid'])) {
            $productModel->andWhere(['uid' => $data['uid']]);
        }
        if (!empty($data['category_id'])) {
            $productModel->andWhere(['category' => $data['category_id']]);
        }
        if ($data['status'] != -1) {
            $productModel->andWhere(['status' => $data['status']]);
        }
        $productCountModel = clone $productModel;
        $count = $productCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $productModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        $reasonList = Yii::$app->params['reasonList'];

        // 获取用户信息
        $uidArr = ArrayHelper::getColumn($list, 'uid');
        $userArr = [];
        if (!empty($uidArr)) {
            $userArr = User::find()->select(['id', 'nickname', 'wx', 'phone', 'wx_openid'])->where(['id' => $uidArr])->asArray()->indexBy('id')->all();
        }

        // 获取置顶信息
        $productIdArr = ArrayHelper::getColumn($list, 'id');
        $stickArr = [];
        if (!empty($productIdArr)) {
            $stickArr = Stick::find()->where(['product_id' => $productIdArr])->asArray()->indexBy('product_id')->all();
        }

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $imageUrl = [];
                foreach ($picList as $url) {
                    $imageUrl[] = ToolsHelper::getLocalImg($url, '', 540);
                }

                // 检查是否关注公众号
                $subscribe = 0;
                if (!empty($userArr[$value['uid']]['wx_openid']) && !YII_ENV_DEV) {
                    $officialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $userArr[$value['uid']]['wx_openid']])->scalar();
                    if (!empty($officialOpenid)) {
                        $officialAccountService = new OfficialAccountService();
                        $officialAccountInfo = $officialAccountService->getUserInfo($officialOpenid);
                        $subscribe = intval($officialAccountInfo['subscribe']);
                    }
                }


                $reasonId = array_search($value['audit_reason'], $reasonList);
                $value['reason_id'] = intval($reasonId);
                $value['product_title'] = $value['title'];
                $value['pics'] = $imageUrl;
                $value['nickname'] = !empty($userArr[$value['uid']]['nickname']) ? $userArr[$value['uid']]['nickname'] : '';
                $value['wx'] = !empty($userArr[$value['uid']]['wx']) ? $userArr[$value['uid']]['wx'] : '';
                $value['phone'] = !empty($userArr[$value['uid']]['phone']) ? $userArr[$value['uid']]['phone'] : '';
                $value['subscribe'] = $subscribe ? "已关注" : "未关注";
                $value['category_name'] = ProductCategory::find()->select(['category_name'])->where(['id' => $value['category']])->scalar();
                $value['status_name'] = Yii::$app->params['productStatus'][$value['status']];
                $value['updated_at'] = !empty($value['updated_at']) ? date("Y-m-d H:i:s", $value['updated_at']) : '';

                // 置顶信息
                $value['stick_status'] = isset($stickArr[$value['id']]) ? $stickArr[$value['id']]['status'] : 0;
                $value['page_type'] = isset($stickArr[$value['id']]) ? $stickArr[$value['id']]['type'] : 0;
                $value['activity_id'] = isset($stickArr[$value['id']]) ? $stickArr[$value['id']]['activity_id'] : 0;
                $value['start_date'] = !empty($stickArr[$value['id']]['start_time']) ? date("Y-m-d", $stickArr[$value['id']]['start_time']) : '';
                $value['end_date'] = !empty($stickArr[$value['id']]['end_time']) ? date("Y-m-d", $stickArr[$value['id']]['end_time']) : '';
            }
        }

        return ToolsHelper::funcReturn(
            "商品列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 审核通过
     *
     * @param     $id
     * @param     $categoryId
     * @param int $isCheat
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/18 15:52
     */
    public function pass($id, $categoryId, $isCheat = 0)
    {
        $now = time();
        $productModel = Product::find()->where(['id' => $id])->one();
        if (empty($productModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($productModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 如果不填分类id，则使用原分类id
        if (empty($categoryId)) {
            $categoryId = $productModel->category;
        }
        $categoryName = '';
        if (!empty($categoryId)) {
            $categoryName = ProductCategory::find()->select(['category_name'])->where(['id' => $categoryId])->scalar();

            // 若存在同分类物品，则设置为作弊
            $existCategoryProduct = Product::find()->where(['uid' => $productModel->uid, 'category' => $categoryId])->limit(1)->exists();
            if (empty($isCheat) && $existCategoryProduct) {
                $isCheat = 1;
            }
        }

        // 检查用户的微信是否绑定，若未绑定，直接审核不通过
        $userInfo = User::find()->where(['id' => $productModel->uid])->asArray()->one();

        // 关注公众号
        $isSubscribe = 0;
        $officialOpenid = UnionOpenid::find()->select("official_openid")->where(['wx_openid' => $userInfo['wx_openid']])->scalar();
        if (!empty($officialOpenid)) {
            $officialAccountService = new OfficialAccountService();
            $officialUserInfo = $officialAccountService->getUserInfo($officialOpenid);
            $isSubscribe = $officialUserInfo['subscribe'];
        }

        if (!$isSubscribe) {
            return $this->refuse($id, "请关联公众号");
        }

        // 修改数据库
        $productModel->category = $categoryId;
        $productModel->category_id = $categoryId;
        $productModel->status = CommonProductService::STAUTS_PASS;
        $productModel->audit_at = $now;
        $productModel->updated_at = $now;
        if ($productModel->save()) {
            //写入geoRedis
            $commonProductService = new CommonProductService();
            $commonProductService->addProductGeoData($id, $productModel->lat, $productModel->lng);

            // 生成到ES
            $data = $productModel->attributes;
            $data['category_name'] = $categoryName;

            $userInfo = User::find()->where(['id' => $productModel->uid])->asArray()->one();
            $data['nickname'] = $userInfo['nickname'];
            $data['avatar'] = $userInfo['avatar'];
            $data['gender'] = $userInfo['gender'];

            $productEsData = EsProduct::get($data['id']);
            if (empty($productEsData)) { // 新增
                $data['price'] = floatval($productModel->price);
                if (EsProduct::insert($data['id'], $data)) {
                    $rewardPoint = 0;
                    if (empty($isCheat)) {
                        // 发布宝贝，审核通过则进行增加积分
                        $rewardPointService = new RewardPointService(RewardPointService::PUBLISH_AWARD_TYPE, $productModel->uid, $now);
                        $rewardRes = $rewardPointService->awardPoint();
                        if ($rewardRes['result']) {
                            $rewardPoint = $rewardRes['data']['point'];
                        }
                    }

                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => MessageService::SYSTEM_USER
                                        ],
                                        'productId' => $productModel->id,
                                        'messageType' => MessageService::SYSTEM_AUDIT_PASS_MESSAGE,
                                        'rewardPoint' => $rewardPoint,
                                        'isCheat' => $isCheat,
                                    ]
                                ]
                            ]
                        )
                    );

                    // 记录到模板消息推送列表中
                    $templateMsgService = new TemplateMsgService();
                    $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $productModel->uid, $productModel->id, TemplateMsgService::PRODUCT_AUDIT_PASS_TMP_MSG);

                    return ToolsHelper::funcReturn("操作成功", true, ['reward_point' => $rewardPoint]);
                }
            } else { // 修改
                unset($data['updated_at']); // 修改不更新ES里的更新时间
                $data['price'] = floatval($productModel->price);
                $data['cut_price'] = intval($productEsData['price'] - $productModel->price);
                if (EsProduct::update($data['id'], $data)) {
                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => MessageService::SYSTEM_USER
                                        ],
                                        'productId' => $productModel->id,
                                        'messageType' => MessageService::SYSTEM_AUDIT_PASS_MESSAGE
                                    ]
                                ]
                            ]
                        )
                    );
                    // 记录到模板消息推送列表中
                    $templateMsgService = new TemplateMsgService();
                    $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $productModel->uid, $productModel->id, TemplateMsgService::PRODUCT_AUDIT_PASS_TMP_MSG);
                    return ToolsHelper::funcReturn("操作成功", true);
                }
            }
            return ToolsHelper::funcReturn("数据库写入成功，ES更新失败", true);
        }
        return ToolsHelper::funcReturn("操作失败", true);
    }

    /**
     * 审核不通过
     *
     * @param $id
     * @param $auditReason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:01
     */
    public function refuse($id, $auditReason)
    {
        $now = time();
        $productModel = Product::find()->where(['id' => $id])->one();
        if (empty($productModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($productModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 修改数据库
        $productModel->status = CommonProductService::STATUS_REFUSE;
        $productModel->audit_reason = $auditReason;
        $productModel->audit_at = $now;
        $productModel->updated_at = $now;
        if ($productModel->save()) {
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => MessageService::SYSTEM_USER
                                ],
                                'productId' => $productModel->id,
                                'messageType' => MessageService::SYSTEM_AUDIT_REFUSE_MESSAGE,
                                'audit_reason' => $auditReason
                            ]
                        ]
                    ]
                )
            );
            // 记录到模板消息推送列表中
            $templateMsgService = new TemplateMsgService();
            $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $productModel->uid, $productModel->id, TemplateMsgService::PRODUCT_AUDIT_REFUSE_TMP_MSG);

            return ToolsHelper::funcReturn("审核不通过成功", true);
        }
        return ToolsHelper::funcReturn("审核不通过失败", true);
    }

    /**
     * 强制下架并删除
     *
     * @param $id
     * @param $auditReason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/9 19:58
     */
    public function down($id, $auditReason)
    {
        $now = time();
        $productModel = Product::find()->where(['id' => $id])->one();
        if (empty($productModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($productModel->status != 1) {
            return ToolsHelper::funcReturn("非已上线商品");
        }

        // 修改数据库
        $productModel->status = CommonProductService::STATUS_DEL;
        $productModel->audit_reason = $auditReason;
        $productModel->audit_at = $now;
        $productModel->updated_at = $now;
        if ($productModel->save()) {
            $data = [
                'status' => $productModel->status,
                'updated_at' => $productModel->updated_at
            ];
            if (EsProduct::update($id, $data)) {
                // 发送系统消息
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => [
                                [
                                    'userInfo' => [
                                        'uid' => MessageService::SYSTEM_USER
                                    ],
                                    'productId' => $productModel->id,
                                    'messageType' => MessageService::SYSTEM_AUDIT_DOWN_MESSAGE,
                                    'audit_reason' => $auditReason
                                ]
                            ]
                        ]
                    )
                );

                // 记录到模板消息推送列表中
                $templateMsgService = new TemplateMsgService();
                $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $productModel->uid, $productModel->id, TemplateMsgService::PRODUCT_AUDIT_DOWN_TMP_MSG);

                return ToolsHelper::funcReturn("操作成功", true);
            }
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 修改商品的分类
     *
     * @param $id
     * @param $categoryId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/21 16:52
     */
    public function category($id, $categoryId)
    {
        $categoryName = ProductCategory::find()->select(['category_name'])->where(['id' => $categoryId])->scalar();
        if (empty($categoryName)) {
            return ToolsHelper::funcReturn('分类ID不存在');
        }

        $productModel = Product::find()->where(['id' => $id])->one();
        if (empty($productModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }

        $productModel->category = $categoryId;
        if ($productModel->save()) {
            // 修改es
            $productEsData = EsProduct::get($id);
            if (!empty($productEsData)) {
                $data = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                ];
                if (EsProduct::update($id, $data)) {
                    return ToolsHelper::funcReturn("修改ES数据成功", true);
                }
                return ToolsHelper::funcReturn("修改ES数据失败");
            }
            return ToolsHelper::funcReturn("修改数据库成功", true);
        }
        return ToolsHelper::funcReturn("修改数据库失败");
    }


    /**
     * 加入置顶
     *
     * @param array $params
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/21 11:37
     */
    public function stick($params = [])
    {
        if (!empty($params['status'])) { // 置顶
            return $this->addStickProduct($params);
        } else { // 取消置顶
            return $this->removeStickProduct($params);
        }
    }

    /**
     * 置顶
     *
     * @param array $params
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/22 17:07
     */
    private function addStickProduct($params = [])
    {
        $time = time();
        $productInfo = Product::find()->where(['id' => $params['id']])->asArray()->one();
        if ($productInfo['status'] != 1) {
            return ToolsHelper::funcReturn("非上架物品不可置顶");
        }

        $stickModel = Stick::find()->where(['product_id' => $productInfo['id']])->one();
        if (empty($stickModel)) {
            $stickModel = new Stick();
            $params['created_at'] = $time;
        }
        $params['product_id'] = $productInfo['id'];
        $params['lat'] = $productInfo['lat'];
        $params['lng'] = $productInfo['lng'];
        $params['start_time'] = strtotime($params['start_date']);
        $params['end_time'] = strtotime($params['end_date']) + 86399;
        $params['status'] = 1;
        $params['updated_at'] = $time;

        $stickModel->attributes = $params;
        if ($stickModel->save()) {
            /** @var \Redis $redisClient */
            $redisClient = Yii::$app->get('redisGeo');
            $redisKey = RedisHelper::RK('distGeoStick', $params['type'], $params['activity_id']);

            if ($redisClient->geoadd($redisKey, $params['lng'], $params['lat'], $productInfo['id'])) {
                return ToolsHelper::funcReturn("置顶成功", true);
            }
        }
        return ToolsHelper::funcReturn("置顶失败");
    }


    /**
     * 取消置顶
     *
     * @param array $params
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/22 17:07
     */
    private function removeStickProduct($params = [])
    {
        $time = time();
        $stickModel = Stick::find()->where(['product_id' => $params['id']])->one();
        if (empty($stickModel)) {
            return ToolsHelper::funcReturn("数据异常");
        }

        $stickModel->status = 0;
        $stickModel->updated_at = $time;
        if ($stickModel->save()) {
            /** @var \Redis $redisClient */
            $redisClient = Yii::$app->get('redisGeo');
            $redisKey = RedisHelper::RK('distGeoStick', $stickModel->type, $stickModel->activity_id);

            if ($redisClient->zrem($redisKey, $params['id'])) {
                return ToolsHelper::funcReturn("取消置顶成功", true);
            }
        }
        return ToolsHelper::funcReturn("取消置顶失败");
    }

    /**
     * 查找分类
     *
     * @param $keyword
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/11 18:03
     */
    public function getCategoryListByKeyword($keyword)
    {
        return ProductCategory::find()->select(['category_name'])->where(['status' => 1, 'category_level' => 3])->andFilterWhere(['LIKE', 'category_name', $keyword])->indexBy('id')->column();
    }

}