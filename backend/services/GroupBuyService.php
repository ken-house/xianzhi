<?php
/**
 * 团购服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/12/1 16:01
 */

namespace backend\services;

use common\helpers\ToolsHelper;
use common\models\GroupBuyDsp;
use common\models\GroupBuyOrder;
use common\models\GroupBuyProduct;
use common\models\GroupBuyProductOption;
use common\models\GroupBuyShop;
use common\models\GroupBuyShopManager;
use common\models\GroupBuyWithdraw;
use common\models\User;
use common\services\WechatPayService;
use yii\helpers\ArrayHelper;
use common\services\GroupBuyService as CommonGroupBuyService;
use Yii;

class GroupBuyService
{
    /**
     * 团购商品
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 16:45
     */
    public function getProductList($data)
    {
        $groupBuyProductModel = GroupBuyProduct::find()->andFilterWhere(['LIKE', 'title', $data['title']]);
        if (!empty($data['id'])) {
            $groupBuyProductModel->andWhere(['id' => $data['id']]);
        }
        if ($data['status'] != -1) {
            $groupBuyProductModel->andWhere(['status' => $data['status']]);
        }
        $groupBuyProductCountModel = clone $groupBuyProductModel;
        $count = $groupBuyProductCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $groupBuyProductModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        // 获取商家信息
        $shopIdArr = ArrayHelper::getColumn($list, 'shop_id');
        $shopArr = [];
        if (!empty($shopIdArr)) {
            $shopArr = GroupBuyShop::find()->select(['id', 'shop_name', 'phone', 'location'])->where(['id' => $shopIdArr])->asArray()->indexBy('id')->all();
        }

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $imageUrl = [];
                foreach ($picList as $url) {
                    $imageUrl[] = ToolsHelper::getLocalImg($url, '', 240);
                }
                $value['product_title'] = $value['title'];
                $value['pics'] = $imageUrl;
                $value['shop_name'] = !empty($shopArr[$value['shop_id']]['shop_name']) ? $shopArr[$value['shop_id']]['shop_name'] : '';
                $value['phone'] = !empty($shopArr[$value['shop_id']]['phone']) ? $shopArr[$value['shop_id']]['phone'] : '';
                $value['status_name'] = Yii::$app->params['groupBuyProductStatus'][$value['status']];
                $value['updated_at'] = !empty($value['updated_at']) ? date("Y-m-d H:i:s", $value['updated_at']) : '';
                $startDate = !empty($value['start_at']) ? date("Y-m-d", $value['start_at']) : '';
                $endDate = !empty($value['end_at']) ? date("Y-m-d", $value['end_at']) : '';
                $value['activity_time'] = $startDate . "-" . $endDate;
                $value['limit_num'] = $value['max_user_buy_num'] . "/" . $value['max_num'];
                $value['is_distribute'] = !empty($value['is_distribute']) ? "是" : "否";
                $value['commission_rate'] = ($value['commission_rate'] * 100) . "%";
                $value['shop_commission_rate'] = ($value['shop_commission_rate'] * 100) . "%";
                $value['refund_type'] = Yii::$app->params['groupBuyRefundType'][$value['refund_type']];
            }
        }

        return ToolsHelper::funcReturn(
            "团购商品列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 团购详情
     *
     * @param $id
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/12/1 19:01
     */
    public function getProductInfo($id)
    {
        $productInfo = GroupBuyProduct::find()->where(['id' => $id])->asArray()->one();
        $shopName = GroupBuyShop::find()->select(['shop_name'])->where(['id' => $productInfo['shop_id']])->scalar();
        $picList = json_decode($productInfo['pics'], true);
        $imageUrl = [];
        foreach ($picList as $url) {
            $imageUrl[] = ToolsHelper::getLocalImg($url);
        }
        $productInfo['pics'] = $imageUrl;
        $productInfo['start_date'] = !empty($productInfo['start_at']) ? date("Y-m-d", $productInfo['start_at']) : '';
        $productInfo['end_date'] = !empty($productInfo['end_at']) ? date("Y-m-d", $productInfo['end_at']) : '';
        $productInfo['commission_rate'] = $productInfo['commission_rate'] * 100;
        $productInfo['shop_commission_rate'] = $productInfo['shop_commission_rate'] * 100;
        $productInfo['shop_name'] = $shopName;
        return $productInfo;
    }

    /**
     * 更改团购活动状态
     *
     * @param $id
     * @param $status
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 16:51
     */
    public function updateProductStatus($id, $status)
    {
        $now = time();
        if (empty($id) || empty($status)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $groupBuyProductModel = GroupBuyProduct::find()->where(['id' => $id])->one();
        $groupBuyProductModel->status = $status;
        $groupBuyProductModel->updated_at = $now;
        if ($groupBuyProductModel->save()) {
            if ($status == CommonGroupBuyService::PRODUCT_PASS_STATUS) { //将坐标写入到redis
                // 读取商家所在位置
                $shopInfo = GroupBuyShop::find()->select(['lat', 'lng'])->where(['id' => $groupBuyProductModel['shop_id']])->asArray()->one();

                $commonGroupBuyService = new CommonGroupBuyService();
                $commonGroupBuyService->addGroupProductGeoData($id, $shopInfo['lat'], $shopInfo['lng']);
            }
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 添加/修改团购信息
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 17:06
     */
    public function saveProduct($data = [])
    {
        $now = time();

        $groupBuyProductModel = GroupBuyProduct::find()->where(['id' => $data['id']])->one();
        if (empty($groupBuyProductModel)) {
            $groupBuyProductModel = new GroupBuyProduct();
            $data['created_at'] = $now;
        }

        if (!empty($data['is_distribute'])) {
            $data['commission_rate'] = $data['commission_rate'] / 100;
        } else {
            $data['commission_rate'] = 0;
        }
        // 商家佣金
        if (!empty($data['shop_commission_rate'])) {
            $data['shop_commission_rate'] = $data['shop_commission_rate'] / 100;
        }

        $data['start_at'] = !empty($data['start_date']) ? strtotime($data['start_date']) : 0;
        $data['end_at'] = !empty($data['end_date']) ? strtotime($data['end_date']) + 86399 : 0;
        if (empty($data['pics'])) {
            $data['pics'] = [];
        } else {
            foreach ($data['pics'] as $key => $imageUrl) {
                $data['pics'][$key] = str_replace(Yii::$app->params['assetDomain'], "", $imageUrl);
            }
        }
        $data['pics'] = json_encode($data['pics'], JSON_UNESCAPED_UNICODE);
        $data['updated_at'] = $now;

        $groupBuyProductModel->loadDefaultValues();
        $groupBuyProductModel->attributes = $data;
        if ($groupBuyProductModel->save()) {
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 商家列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 18:54
     */
    public function getShopList($data = [])
    {
        $groupBuyShopModel = GroupBuyShop::find()->andFilterWhere(['LIKE', 'shop_name', $data['keyword']]);
        if (!empty($data['id'])) {
            $groupBuyShopModel->andWhere(['id' => $data['id']]);
        }
        if ($data['status'] != -1) {
            $groupBuyShopModel->andWhere(['status' => $data['status']]);
        }
        $groupBuyShopCountModel = clone $groupBuyShopModel;
        $count = $groupBuyShopCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $groupBuyShopModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        $statusList = Yii::$app->params['groupBuyShopStatus'];
        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $imageUrl = [];
                foreach ($picList as $url) {
                    $imageUrl[] = ToolsHelper::getLocalImg($url, '', 240);
                }
                $value['pics'] = $imageUrl;
                $value['updated_at'] = !empty($value['updated_at']) ? date("Y-m-d H:i:s", $value['updated_at']) : '';
                $value['status_name'] = $statusList[$value['status']];
                $value['shop_avatar'] = ToolsHelper::getLocalImg($value['shop_avatar'], '', 240);
                $value['commission_rate'] = ($value['commission_rate'] * 100) . "%";
            }
        }

        return ToolsHelper::funcReturn(
            "团购商家列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 店铺详情
     *
     * @param $id
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/12/1 19:02
     */
    public function getShopInfo($id)
    {
        $shopInfo = GroupBuyShop::find()->where(['id' => $id])->asArray()->one();
        $shopInfo['shop_avatar'] = ToolsHelper::getLocalImg($shopInfo['shop_avatar']);
        $shopInfo['shop_logo'] = ToolsHelper::getLocalImg($shopInfo['shop_logo']);
        $shopInfo['commission_rate'] = $shopInfo['commission_rate'] * 100;
        $picList = json_decode($shopInfo['pics'], true);
        $imageUrl = [];
        foreach ($picList as $url) {
            $imageUrl[] = ToolsHelper::getLocalImg($url);
        }
        $shopInfo['pics'] = $imageUrl;
        return $shopInfo;
    }

    /**
     * 店铺合作状态
     *
     * @param $id
     * @param $status
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/2 11:26
     */
    public function updateShopStatus($id, $status)
    {
        $now = time();
        if (empty($id) || empty($status)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $res = GroupBuyShop::updateAll(['status' => $status, 'updated_at' => $now], ['id' => $id]);
        if ($res) {
            if ($status == CommonGroupBuyService::SHOP_STOPCOOP_STATUS) { // 停止合作，下架所有团购活动
                GroupBuyProduct::updateAll(['status' => CommonGroupBuyService::PRODUCT_DOWN_STATUS, 'updated_at' => $now], ['shop_id' => $id, 'status' => 1]);
            }
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 新增/修改商家
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:06
     */
    public function saveShop($data = [])
    {
        $now = time();

        $groupBuyShopModel = GroupBuyShop::find()->where(['id' => $data['id']])->one();
        if (empty($groupBuyShopModel)) {
            $groupBuyShopModel = new GroupBuyShop();
            $data['created_at'] = $now;
        }
        if (!empty($data['shop_avatar'])) {
            $data['shop_avatar'] = str_replace(Yii::$app->params['assetDomain'], "", $data['shop_avatar']);
        }
        if (!empty($data['shop_logo'])) {
            $data['shop_logo'] = str_replace(Yii::$app->params['assetDomain'], "", $data['shop_logo']);
        }
        $data['commission_rate'] = $data['commission_rate'] / 100;

        if (empty($data['pics'])) {
            $data['pics'] = [];
        } else {
            foreach ($data['pics'] as $key => $imageUrl) {
                $data['pics'][$key] = str_replace(Yii::$app->params['assetDomain'], "", $imageUrl);
            }
        }
        $data['pics'] = json_encode($data['pics'], JSON_UNESCAPED_UNICODE);
        $data['updated_at'] = $now;

        $groupBuyShopModel->loadDefaultValues();
        $groupBuyShopModel->attributes = $data;
        if ($groupBuyShopModel->save()) {
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 管理员列表
     *
     * @param $shopId
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/12/1 19:15
     */
    public function getShopManagerList($shopId)
    {
        $managerList = GroupBuyShopManager::find()->where(['shop_id' => $shopId])->asArray()->all();
        if (!empty($managerList)) {
            $uidArr = ArrayHelper::getColumn($managerList, 'uid');
            $userList = User::find()->where(['id' => $uidArr])->asArray()->indexBy("id")->all();
            foreach ($managerList as $key => &$value) {
                $value['nickname'] = $userList[$value['uid']]['nickname'];
                $value['phone'] = $userList[$value['uid']]['phone'];
                $value['avatar'] = $userList[$value['uid']]['avatar'];
                $value['role_name'] = empty($value['role_id']) ? '普通管理员' : '超级管理员';
                $value['status_name'] = Yii::$app->params['groupBuyShopManagerStatus'][$value['status']];
                $value['updated_at'] = date("Y-m-d H:i:s", $value['updated_at']);
            }
        }
        return $managerList;
    }

    /**
     * 商家管理员状态修改
     *
     * @param $id
     * @param $status
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:19
     */
    public function managerStatus($id, $status)
    {
        $now = time();
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        if ($status == 2) { // 删除
            $res = GroupBuyShopManager::deleteAll(['id' => $id]);
        } else {
            $res = GroupBuyShopManager::updateAll(['status' => $status, 'updated_at' => $now], ['id' => $id]);
        }
        if ($res) {
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 商家管理员添加（一个用户只能为一家商家管理员）
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:29
     */
    public function managerAdd($data)
    {
        $now = time();
        $groupBuyShopManagerModel = GroupBuyShopManager::find()->where(['uid' => $data['uid']])->one();
        if (empty($groupBuyShopManagerModel)) {
            // 判断是否为分销店主
            $isDsp = GroupBuyDsp::find()->where(['uid' => $data['uid'], 'status' => CommonGroupBuyService::DSP_PASS_STATUS])->exists();
            if ($isDsp) {
                return ToolsHelper::funcReturn("该用户为分销店主");
            }
            $groupBuyShopManagerModel = new GroupBuyShopManager();
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $groupBuyShopManagerModel->loadDefaultValues();
            $groupBuyShopManagerModel->attributes = $data;
            if ($groupBuyShopManagerModel->save()) {
                return ToolsHelper::funcReturn("添加成功", true);
            }
            return ToolsHelper::funcReturn("添加失败");
        }
        return ToolsHelper::funcReturn("帐号已存在，商家id为：" . $groupBuyShopManagerModel['shop_id']);
    }

    /**
     * 分销店主列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 20:50
     */
    public function getDspList($data = [])
    {
        $groupBuyDspModel = GroupBuyDsp::find();
        if (!empty($data['uid'])) {
            $groupBuyDspModel->andWhere(['uid' => $data['uid']]);
        }
        if ($data['status'] != -1) {
            $groupBuyDspModel->andWhere(['status' => $data['status']]);
        }
        $groupBuyDspCountModel = clone $groupBuyDspModel;
        $count = $groupBuyDspCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $groupBuyDspModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        if (!empty($list)) {
            $uidArr = ArrayHelper::getColumn($list, 'uid');
            $userDataArr = User::find()->select(['id', 'nickname', 'phone'])->where(['id' => $uidArr])->asArray()->indexBy('id')->all();
            foreach ($list as $key => &$value) {
                $value['nickname'] = $userDataArr[$value['uid']]['nickname'];
                $value['phone'] = $userDataArr[$value['uid']]['phone'];
                $value['audit_at'] = !empty($value['audit_at']) ? date("Y-m-d H:i:s", $value['audit_at']) : '';
                $value['open_at'] = !empty($value['open_at']) ? date("Y-m-d H:i:s", $value['open_at']) : '';
                $value['status_name'] = Yii::$app->params['groupBuyDspStatus'][$value['status']];
            }
        }

        return ToolsHelper::funcReturn(
            "团购分销店主列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 分销店主审核
     *
     * @param        $id
     * @param string $reason
     * @param string $action
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 21:00
     */
    public function dspAudit($id, $reason = "", $action = "pass")
    {
        $now = time();
        $groupBuyDspModel = GroupBuyDsp::find()->where(['id' => $id])->one();
        if ($action == "pass") {
            // 判断是否为商家管理员
            $isShopManager = GroupBuyShopManager::find()->where(['uid' => $groupBuyDspModel['uid']])->exists();
            if ($isShopManager) {
                return ToolsHelper::funcReturn("该用户为商家帐号");
            }
            $groupBuyDspModel->status = 1;
            $groupBuyDspModel->open_at = $now;
        } elseif ($action == "refuse") {
            $groupBuyDspModel->status = 2;
            $groupBuyDspModel->audit_reason = $reason;
        } else {
            $groupBuyDspModel->status = 3;
            $groupBuyDspModel->audit_reason = $reason;
        }
        $groupBuyDspModel->updated_at = $now;
        $groupBuyDspModel->audit_at = $now;
        if ($groupBuyDspModel->save()) {
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 订单列表
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 21:35
     */
    public function getOrderList($data)
    {
        $groupBuyOrderModel = GroupBuyOrder::find()->andFilterWhere(['LIKE', 'title', $data['keyword']]);
        if (!empty($data['uid'])) {
            $groupBuyOrderModel->andWhere(['uid' => $data['uid']]);
        }
        if (!empty($data['dsp_uid'])) {
            $groupBuyOrderModel->andWhere(['dsp_uid' => $data['dsp_uid']]);
        }
        if (!empty($data['shop_id'])) {
            $groupBuyOrderModel->andWhere(['shop_id' => $data['shop_id']]);
        }
        if ($data['status'] != -1) {
            $groupBuyOrderModel->andWhere(['status' => $data['status']]);
        }
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $groupBuyOrderModel->andWhere(['>=', 'updated_at', $data['start_date']])->andWhere(['<=', 'updated_at', $data['end_date']]);
        }
        $groupBuyOrderCountModel = clone $groupBuyOrderModel;
        $count = $groupBuyOrderCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $groupBuyOrderModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        // 获取商家信息
        $shopIdArr = ArrayHelper::getColumn($list, 'shop_id');
        $shopArr = [];
        if (!empty($shopIdArr)) {
            $shopArr = GroupBuyShop::find()->select(['id', 'shop_name', 'phone', 'location'])->where(['id' => $shopIdArr])->asArray()->indexBy('id')->all();
        }

        // 获取用户信息
        $uidArr = ArrayHelper::getColumn($list, 'uid');
        $dspUidArr = ArrayHelper::getColumn($list, 'dsp_uid');
        $finishUidArr = ArrayHelper::getColumn($list, 'finish_uid');
        $uidArr = array_merge($uidArr, $dspUidArr, $finishUidArr);
        $userDataArr = [];
        if (!empty($uidArr)) {
            $userDataArr = User::find()->select(['id', 'nickname', 'phone'])->where(['id' => $uidArr])->asArray()->indexBy('id')->all();
        }

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyOrderStatus'];

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $value['product_cover'] = ToolsHelper::getLocalImg($value['product_cover'], '', 240);
                $value['nickname'] = !empty($userDataArr[$value['uid']]['nickname']) ? $userDataArr[$value['uid']]['nickname'] . "(" . $value['uid'] . ")" : '';
                $value['shop_name'] = !empty($shopArr[$value['shop_id']]['shop_name']) ? $shopArr[$value['shop_id']]['shop_name'] . "(" . $value['shop_id'] . ")" : '';
                $value['dsp_nickname'] = !empty($userDataArr[$value['dsp_uid']]['nickname']) ? $userDataArr[$value['dsp_uid']]['nickname'] . "(" . $value['dsp_uid'] . ")" : '';
                $value['status_name'] = Yii::$app->params['groupBuyOrderStatus'][$value['status']];
                $value['refund_type'] = Yii::$app->params['groupBuyRefundType'][$value['refund_type']];
                $value['pay_at'] = !empty($value['pay_at']) ? date("Y-m-d H:i:s", $value['pay_at']) : '';
                $value['delete_at'] = !empty($value['delete_at']) ? date("Y-m-d H:i:s", $value['delete_at']) : '';
                $value['finish_at'] = !empty($value['finish_at']) ? date("Y-m-d H:i:s", $value['finish_at']) : '';
                $value['finish_nickname'] = !empty($userDataArr[$value['finish_uid']]['nickname']) ? $userDataArr[$value['finish_uid']]['nickname'] . "(" . $value['finish_uid'] . ")" : '';
                $value['refund_apply_at'] = !empty($value['refund_apply_at']) ? date("Y-m-d H:i:s", $value['refund_apply_at']) : '';
                $value['refund_audit_at'] = !empty($value['refund_audit_at']) ? date("Y-m-d H:i:s", $value['refund_audit_at']) : '';
                $value['refund_finish_at'] = !empty($value['refund_finish_at']) ? date("Y-m-d H:i:s", $value['refund_finish_at']) : '';
                $value['created_at'] = !empty($value['created_at']) ? date("Y-m-d H:i:s", $value['created_at']) : '';
            }
        }

        return ToolsHelper::funcReturn(
            "团购订单列表",
            true,
            [
                'list' => $list,
                'statusList' => $statusList,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }


    /**
     * 退款 (后台操作)
     *
     * @param        $orderId
     * @param string $reason
     * @param int    $refundAmount
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/25 11:55
     */
    public function orderRefund($orderId, $reason = "", $refundAmount = 0)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if ($orderData['status'] != CommonGroupBuyService::ORDER_REFUND_APPLY) {
            return ToolsHelper::funcReturn("订单未提交退款申请");
        }

        // 生成退款订单号
        $refundNo = ToolsHelper::buildTradeNo($orderData['uid'], 2);

        // 退款金额
        if (empty($refundAmount)) {
            $refundAmount = $orderData['order_amount'];
        }

        // 微信退款
        $payParams = [
            'out_trade_no' => $orderData['order_no'],
            'refund_no' => $refundNo,
            'reason' => !empty($reason) ? $reason : '用户申请退款',
            'refund_price' => $refundAmount * 100,
            'order_amount' => $orderData['order_amount'] * 100,
        ];
        $wechatPayService = new WechatPayService();
        $data = $wechatPayService->refundOrder($payParams);
        if (!empty($data)) {
            if ($data['status'] == "SUCCESS" || $data['status'] == "PROCESSING") {
                $orderRes = GroupBuyOrder::updateAll(['status' => CommonGroupBuyService::ORDER_REFUND_PROCESS, 'refund_audit_at' => $now, 'updated_at' => $now], ['id' => $orderId]);
                if ($orderRes) {
                    return ToolsHelper::funcReturn("退款已提交，处理中", true);
                }
                Yii::info(['func_name' => 'GroupBuyController.orderRefund', 'order_id' => $orderId, 'orderRes' => $orderRes], 'trace');
                return ToolsHelper::funcReturn("退款处理中，订单状态变更失败");
            }
            return ToolsHelper::funcReturn("退款失败");
        } else {
            return ToolsHelper::funcReturn("退款失败");
        }
    }

    /**
     * 退款拒绝
     *
     * @param $orderId
     * @param $reason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/25 15:05
     */
    public function orderRefundRefuse($orderId, $reason)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderData) || $orderData['status'] != CommonGroupBuyService::ORDER_REFUND_APPLY) {
            return ToolsHelper::funcReturn("订单未提交退款申请");
        }

        $orderRes = GroupBuyOrder::updateAll(['status' => CommonGroupBuyService::ORDER_REFUND_FAIL, 'refund_fail_reason' => $reason, 'refund_audit_at' => $now, 'updated_at' => $now], ['id' => $orderData['id']]);
        if ($orderRes) {
            return ToolsHelper::funcReturn("退款拒绝成功", true);
        }
        return ToolsHelper::funcReturn("退款拒绝失败");
    }

    /**
     * 提现列表
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/7 18:33
     */
    public function getWithdrawList($data)
    {
        $groupBuyWithdrawModel = GroupBuyWithdraw::find();
        if (!empty($data['uid'])) {
            $groupBuyWithdrawModel->andWhere(['uid' => $data['uid']]);
        }
        if (!empty($data['shop_id'])) {
            $groupBuyWithdrawModel->andWhere(['shop_id' => $data['shop_id']]);
        }
        if ($data['status'] != -1) {
            $groupBuyWithdrawModel->andWhere(['status' => $data['status']]);
        }
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $groupBuyWithdrawModel->andWhere(['>=', 'created_at', $data['start_date']])->andWhere(['<=', 'created_at', $data['end_date']]);
        }
        $groupBuyWithdrawCountModel = clone $groupBuyWithdrawModel;
        $count = $groupBuyWithdrawCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $groupBuyWithdrawModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        // 获取商家信息
        $shopIdArr = ArrayHelper::getColumn($list, 'shop_id');
        $shopArr = [];
        if (!empty($shopIdArr)) {
            $shopArr = GroupBuyShop::find()->select(['id', 'shop_name', 'phone', 'location', 'withdraw_account'])->where(['id' => $shopIdArr])->asArray()->indexBy('id')->all();
        }

        // 获取用户信息
        $uidArr = ArrayHelper::getColumn($list, 'uid');
        $userDataArr = [];
        if (!empty($uidArr)) {
            $userDataArr = User::find()->select(['id', 'nickname', 'phone'])->where(['id' => $uidArr])->asArray()->indexBy('id')->all();
        }

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyWithdrawStatus'];

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $value['nickname'] = !empty($userDataArr[$value['uid']]['nickname']) ? $userDataArr[$value['uid']]['nickname'] . "(" . $value['uid'] . ")" : '';
                $value['shop_name'] = !empty($shopArr[$value['shop_id']]['shop_name']) ? $shopArr[$value['shop_id']]['shop_name'] . "(" . $value['shop_id'] . ")" : '';
                $value['status_name'] = Yii::$app->params['groupBuyWithdrawStatus'][$value['status']];
                $value['audit_at'] = !empty($value['audit_at']) ? date("Y-m-d H:i:s", $value['audit_at']) : '';
                $value['pay_at'] = !empty($value['pay_at']) ? date("Y-m-d H:i:s", $value['pay_at']) : '';
                $value['created_at'] = !empty($value['created_at']) ? date("Y-m-d H:i:s", $value['created_at']) : '';
            }
        }

        return ToolsHelper::funcReturn(
            "提现列表",
            true,
            [
                'list' => $list,
                'statusList' => $statusList,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 提现审核
     *
     * @param        $id
     * @param        $action
     * @param string $reason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/7 19:04
     */
    public function withdrawStatus($id, $action, $reason = '')
    {
        $now = time();
        $groupBuyWithdrawData = GroupBuyWithdraw::find()->where(['id' => $id])->asArray()->one();
        if (empty($groupBuyWithdrawData) || $groupBuyWithdrawData['status'] != CommonGroupBuyService::WITHDRAW_AUDIT_STATUS) {
            return ToolsHelper::funcReturn("非法操作");
        }
        if ($action == "pass") {
            if (!empty($groupBuyWithdrawData['shop_id'])) {
                $isShopManager = GroupBuyShopManager::find()->where(['uid' => $groupBuyWithdrawData['uid'], 'shop_id' => $groupBuyWithdrawData['shop_id'], 'role_id' => 1, 'status' => 1])->exists();
                if (!$isShopManager) {
                    return ToolsHelper::funcReturn("非店铺超级管理员，审核通过失败");
                }
            }
            $res = GroupBuyWithdraw::updateAll(['status' => CommonGroupBuyService::WITHDRAW_PASS_STATUS, 'audit_at' => $now, 'pay_at' => $now, 'updated_at' => $now], ['id' => $id]);
            if ($res) {
                return ToolsHelper::funcReturn("审核通过", true);
            }
            return ToolsHelper::funcReturn("审核通过失败");
        } else {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $res1 = GroupBuyWithdraw::updateAll(['status' => CommonGroupBuyService::WITHDRAW_REFUSE_STATUS, 'audit_at' => $now, 'audit_reason' => $reason, 'updated_at' => $now], ['id' => $id]);

                if (!empty($groupBuyWithdrawData['shop_id'])) { // 商家
                    $res2 = GroupBuyShop::updateAllCounters(['withdraw_amount' => -$groupBuyWithdrawData['amount']], ['id' => $groupBuyWithdrawData['shop_id']]);
                } else { // 分销商
                    $res2 = GroupBuyDsp::updateAllCounters(['withdraw_amount' => -$groupBuyWithdrawData['amount']], ['uid' => $groupBuyWithdrawData['uid']]);
                }

                if ($res1 && $res2) {
                    $transaction->commit();
                    return ToolsHelper::funcReturn("审核拒绝成功", true);
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
            }
            return ToolsHelper::funcReturn("审核拒绝失败");
        }
    }

    /**
     * 商品购买选项列表
     *
     * @param $productId
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/12/1 19:15
     */
    public function getProductOptionList($productId)
    {
        $optionList = GroupBuyProductOption::find()->where(['product_id' => $productId])->orderBy("sort asc,id asc")->asArray()->all();
        if (!empty($optionList)) {
            foreach ($optionList as $key => &$value) {
                $value['status_name'] = $value['status'] == 1 ? '有效' : '已删除';
                $value['updated_at'] = date("Y-m-d H:i:s", $value['updated_at']);
            }
        }
        return $optionList;
    }

    /**
     * 商品购买选项状态修改
     *
     * @param $id
     * @param $status
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:19
     */
    public function productOptionStatus($id, $status)
    {
        $now = time();
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $groupBuyProductOptionModel = GroupBuyProductOption::find()->where(['id' => $id])->one();
        $groupBuyProductOptionModel->status = $status;
        $groupBuyProductOptionModel->updated_at = $now;
        if ($groupBuyProductOptionModel->save()) {
            $res = $this->updateProductPrice($groupBuyProductOptionModel->product_id);
            if (!$res['result']) {
                return $res;
            }
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 商品购买选项详情
     *
     * @param $id
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/12/12 19:35
     */
    public function getProductOptionInfo($id)
    {
        return GroupBuyProductOption::find()->where(['id' => $id])->asArray()->one();
    }

    /**
     * 商品购买选项保存
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/12 18:55
     */
    public function productOptionSave($data)
    {
        $now = time();
        if (empty($data['id'])) { // 新增
            $groupBuyProductOptionModel = new GroupBuyProductOption();
            $groupBuyProductOptionModel->created_at = $now;
        } else {
            $groupBuyProductOptionModel = GroupBuyProductOption::find()->where(['id' => $data['id']])->one();
        }
        $groupBuyProductOptionModel->attributes = $data;
        $groupBuyProductOptionModel->updated_at = $now;
        if ($groupBuyProductOptionModel->save()) {
            $res = $this->updateProductPrice($groupBuyProductOptionModel->product_id);
            if (!$res['result']) {
                return $res;
            }
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 更改商品默认价格
     *
     * @param $productId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/12 19:09
     */
    private function updateProductPrice($productId)
    {
        $price = GroupBuyProductOption::find()->select(['price'])->where(['product_id' => $productId, 'status' => 1])->orderBy("sort asc,id asc")->scalar();
        if (empty($price)) {
            return ToolsHelper::funcReturn("更改商品价格为0");
        }
        $res = GroupBuyProduct::updateAll(['price' => $price, 'updated_at' => time()], ['id' => $productId]);
        if ($res) {
            return ToolsHelper::funcReturn("更新商品价格成功", true);
        }
        return ToolsHelper::funcReturn("更新商品价格失败");
    }


}