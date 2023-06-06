<?php
/**
 * 超值团购服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/15 11:50
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\GroupBuyDsp;
use common\models\GroupBuyOrder;
use common\models\GroupBuyProduct;
use common\models\GroupBuyProductOption;
use common\models\GroupBuyShop;
use common\models\GroupBuyShopManager;
use common\models\GroupBuyWithdraw;
use common\models\User;
use Yii;
use yii\helpers\ArrayHelper;

class GroupBuyService
{
    const PRODUCT_AUDITING_STATUS = 0; // 0 待上架
    const PRODUCT_PASS_STATUS = 1; //  1 上架
    const PRODUCT_DOWN_STATUS = 2; // 2 已下架

    const ORDER_PAYING = 0; // 0 待支付
    const ORDER_PAY_SUCCESS = 1; // 1 已支付
    const ORDER_FINISH = 2;// 2 已核销
    const ORDER_REFUND_APPLY = 3;// 3 申请退款
    const ORDER_REFUND_SUCCESS = 4;// 4 退款完成
    const ORDER_CANCEL = 5;// 5 已取消（已过期）
    const ORDER_NO_DELETE = 0; // 订单正常
    const ORDER_DELETE = 1; // 订单删除
    const ORDER_REFUND_PROCESS = 100; // 100 退款中
    const ORDER_REFUND_FAIL = 101;// 101 退款失败

    const DSP_AUDITING_STATUS = 0; // 审核中
    const DSP_PASS_STATUS = 1;// 审核通过
    const DSP_REFUSE_STATUS = 2; // 审核不通过

    const REFUND_SUPPORT_NO = 0; // 不支持退款
    const REFUND_SUPPORT = 1; // 支持随时退款
    const REFUND_SUPPORT_EXPIRE = 2; // 支持过期自动退款

    const SHOP_AUDIT_STATUS = 0; // 待审核
    const SHOP_COOP_STATUS = 1; // 合作中
    const SHOP_STOPCOOP_STATUS = 2; //停止合作

    const WITHDRAW_AUDIT_STATUS = 0; // 提现审核中
    const WITHDRAW_PASS_STATUS = 1; // 提现审核通过
    const WITHDRAW_REFUSE_STATUS = 2; // 提现审核不通过

    /**
     * 获取团购活动列表
     *
     * @param array $params
     * @param int   $page
     * @param int   $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/16 10:39
     */
    public function getProductList($params = [], $page = 1, $pageSize = 20)
    {
        $productArr = [];
        if (!empty($params['lat'])) {
            $distList = $this->getGroupProductListFromGeoRedis($params['lat'], $params['lng'], 100);
            if (!empty($distList)) {
                foreach ($distList as $key => $value) {
                    $productArr[$value[0]] = $value[1] * 1000;
                }
            }
            if (empty($productArr)) {
                return [];
            }
        }

        // todo 兼容测试
        $versionNum = Yii::$app->request->headers->get('version-num');
        if ($versionNum == Yii::$app->params['auditVersionNum']) {
            $productArr = [
                2 => 10000
            ];
        }


        $start = ($page - 1) * $pageSize;
        $query = GroupBuyProduct::find()->where(['status' => self::PRODUCT_PASS_STATUS])->andFilterWhere(['LIKE', 'title', $params['keyword']]);
        if ($params['is_distribute'] != -1) {
            $query->andWhere(['is_distribute' => $params['is_distribute']]);
        }
        if (!empty($productArr)) {
            $query->andWhere(['id' => array_keys($productArr)]);
        }
        $productList = $query->offset($start)->limit($pageSize)->orderBy(['sort' => 'ASC', 'updated_at' => 'DESC'])->asArray()->all();
        if (!empty($productList)) {
            $shopIdArr = [];
            foreach ($productList as $k => $v) {
                $shopIdArr[] = $v['shop_id'];
            }

            // 查询店铺信息
            $shopDataArr = GroupBuyShop::find()->select(['id', 'shop_name', 'score', 'location', 'shop_avatar'])->where(['id' => $shopIdArr])->indexBy(['id'])->asArray()->all();

            foreach ($productList as $key => &$value) {
                if (!in_array($params['uid'], [100000, 100001, 100041]) && $value['id'] == 2) { // todo 测试数据仅管理员可看
                    unset($productList[$key]);
                    continue;
                }
                $picArr = json_decode($value['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $value['pics'] = $picArr;
                $dist = isset($productArr[$value['id']]) ? $productArr[$value['id']] : 0;
                switch (true) {
                    case $dist > 1000:
                        $value['dist'] = round($dist / 1000, 1) . "km";
                        break;
                    case $dist <= 1000 && $dist > 200:
                        $value['dist'] = intval($dist) . '米';
                        break;
                    case $dist <= 200 && $dist > 0:
                        $value['dist'] = '200米以内';
                        break;
                    default:
                        $value['dist'] = '';
                }
                $value['cover'] = !empty($picArr[0]) ? $picArr[0] : '';
                $value['shop_name'] = isset($shopDataArr[$value['shop_id']]['shop_name']) ? $shopDataArr[$value['shop_id']]['shop_name'] : '';
                $value['shop_score'] = isset($shopDataArr[$value['shop_id']]['score']) ? $shopDataArr[$value['shop_id']]['score'] : 0;
                $value['shop_location'] = isset($shopDataArr[$value['shop_id']]['location']) ? $shopDataArr[$value['shop_id']]['location'] : 0;
                $value['shop_avatar'] = isset($shopDataArr[$value['shop_id']]['shop_avatar']) ? ToolsHelper::getLocalImg($shopDataArr[$value['shop_id']]['shop_avatar'], '', 240) : '';
                $optionRes = $this->getProductOptionList($value['id']);
                if (empty($optionRes['optionInfo']['option_id'])) { // 已售磬
                    unset($productList[$key]);
                    continue;
                }
                $value['option_list'] = array_values($optionRes['optionList']);
                $value = array_merge($value, $optionRes['optionInfo']);
                $value['commission'] = 0;
                if ($value['is_distribute']) {
                    $value['commission'] = round($value['price'] * $value['commission_rate'], 2);
                }
            }
        }
        return array_values($productList);
    }

    /**
     * 商品购买选项
     *
     * @param $productId
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/12/11 15:27
     */
    private function getProductOptionList($productId)
    {
        $optionInfo = [
            'option_id' => 0,
            'option_price' => '',
            'option_original_price' => '',
            'option_sale_num' => 0,
            'option_max_num' => 0,
        ];
        $optionList = GroupBuyProductOption::find()->where(['product_id' => $productId, 'status' => 1])->orderBy("sort asc")->indexBy('id')->asArray()->all();
        if (!empty($optionList)) {
            foreach ($optionList as $key => &$value) {
                $value['disabled'] = 0;
                if (intval($value['max_num']) > 0 && intval($value['sale_num']) >= intval($value['max_num'])) {
                    $value['disabled'] = 1;
                }
                if ($optionInfo['option_id'] == 0) {
                    if (intval($value['max_num']) == 0 || intval($value['sale_num']) < intval($value['max_num'])) {
                        $optionInfo = [
                            'option_id' => $value['id'],
                            'option_price' => $value['price'],
                            'option_original_price' => $value['original_price'],
                            'option_sale_num' => $value['sale_num'],
                            'option_max_num' => $value['max_num'],
                        ];
                    }
                }
            }
        }

        return [
            'optionList' => $optionList,
            'optionInfo' => $optionInfo,
        ];
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
    public function addGroupProductGeoData($productId, $lat, $lng)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoGroupBuy');

        $redisClient->geoadd($redisKey, $lng, $lat, $productId);
    }

    /**
     * 从geoRedis中读取附近的团购商品列表
     *
     * @param $lat
     * @param $lng
     * @param $distType
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:44
     */
    private function getGroupProductListFromGeoRedis($lat, $lng, $distType)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoGroupBuy');
        return $redisClient->georadius($redisKey, floatval($lng), floatval($lat), $distType, 'km', 'WITHDIST');
    }

    /**
     * 团购商品详情
     *
     * @param $userInfo
     * @param $productId
     * @param $optionId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 11:47
     */
    public function getProductInfo($userInfo, $productId, $optionId = 0)
    {
        if (empty($productId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productInfo = GroupBuyProduct::find()->where(['id' => $productId])->asArray()->one();
        if (empty($productInfo)) {
            return ToolsHelper::funcReturn("请在后台关闭微信，重新进入小程序");
        }

        // 佣金
        $productInfo['commission'] = 0;
        if ($productInfo['is_distribute']) {
            $productInfo['commission'] = round($productInfo['price'] * $productInfo['commission_rate'], 2);
        }

        // 封面图片
        $picList = json_decode($productInfo['pics'], true);
        $bannerList = [];
        foreach ($picList as $k => $imgUrl) {
            $imageUrl = ToolsHelper::getLocalImg($imgUrl, '', 540);
            $picList[$k] = $imageUrl;

            $bannerList[] = [
                'app_id' => '',
                'link_url' => '',
                'image_url' => $imageUrl,
            ];
        }
        $productInfo['pics'] = $bannerList;
        $productInfo['picsList'] = $picList;
        $productInfo['cover'] = !empty($picList[0]) ? $picList[0] : '';
        $productInfo['end_time'] = '';
        if ($productInfo['end_at'] > 0) {
            $productInfo['end_time'] = $productInfo['end_at'] > time() ? date("Y-m-d", $productInfo['end_at']) : '已结束';
        }

        // 商品购买选项
        $optionRes = $this->getProductOptionList($productId);
        $productInfo['option_list'] = array_values($optionRes['optionList']);
        $productInfo = array_merge($productInfo, $optionRes['optionInfo']);

        // 这里仅佣金仅作为页面展示，使用product表自身的价格（即第一个选项的价格）
        $productInfo['commission'] = 0;
        if ($productInfo['is_distribute']) {
            $productInfo['commission'] = round($productInfo['price'] * $productInfo['commission_rate'], 2);
        }

        // 店铺信息
        $shopInfo = GroupBuyShop::find()->where(['id' => $productInfo['shop_id']])->asArray()->one();
        if (!empty($shopInfo)) {
            $shopInfo['shop_avatar'] = ToolsHelper::getLocalImg($shopInfo['shop_avatar'], '', 240);
        }

        // 最近分销用户
        $dspList = $this->getLastestDspList();

        // 支付页面购买选项数据
        $payOptionInfo = isset($optionRes['optionList'][$optionId]) ? $optionRes['optionList'][$optionId] : [];

        return ToolsHelper::funcReturn(
            "商品详情",
            true,
            [
                'userInfo' => $userInfo,
                'productInfo' => $productInfo,
                'shopInfo' => $shopInfo,
                'dspList' => $dspList,
                'payOptionInfo' => $payOptionInfo,
            ]
        );
    }

    /**
     * 读取若干个分销用户
     *
     * @param int $limit
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/24 10:26
     */
    public function getLastestDspList($limit = 5)
    {
        $userList = [];
        $uidArr = GroupBuyDsp::find()->select(['uid'])->where(['status' => self::DSP_PASS_STATUS])->column();
        if (count($uidArr) > $limit) {
            $uidArr = array_rand($uidArr, $limit);
        }
        if (!empty($uidArr)) {
            $userList = User::find()->select(['id', 'avatar'])->where(['id' => $uidArr])->asArray()->all();
        }
        return $userList;
    }

    /**
     * 下单支付
     *
     * @param $userInfo
     * @param $productId
     * @param $optionId
     * @param $orderNum
     * @param $remark
     * @param $inviteCode
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 14:54
     */
    public function orderPay($userInfo, $productId, $optionId, $orderNum, $remark, $inviteCode)
    {
        $now = time();

        if (empty($productId) || empty($optionId)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $productInfo = GroupBuyProduct::find()->where(['id' => $productId])->asArray()->one();
        if (empty($productInfo)) {
            return ToolsHelper::funcReturn("非法操作");
        }
        // 判断是否超出剩余份数、活动是否为进行中
        if ($productInfo['status'] != self::PRODUCT_PASS_STATUS) {
            return ToolsHelper::funcReturn("活动已结束");
        }

        if (!empty($productInfo['start_at']) && $productInfo['start_at'] > $now) {
            return ToolsHelper::funcReturn("活动未开始");
        }

        if (!empty($productInfo['end_at']) && $productInfo['end_at'] < $now) {
            return ToolsHelper::funcReturn("活动已结束");
        }

        // 查找商品购买选项对应的数据
        $productOptionInfo = GroupBuyProductOption::find()->where(['id' => $optionId])->asArray()->one();
        if (empty($productOptionInfo) || $productOptionInfo['product_id'] != $productId) {
            return ToolsHelper::funcReturn("非法操作");
        }

        if ($productOptionInfo['status'] != 1) {
            return ToolsHelper::funcReturn("当前购买项已停售");
        }

        // 判断是否超出活动限量
        if (!empty($productOptionInfo['max_num'])) {
            if ($productOptionInfo['max_num'] <= $productOptionInfo['sale_num'] || $productOptionInfo['max_num'] < $productOptionInfo['sale_num'] + $orderNum) {
                return ToolsHelper::funcReturn("剩余份数不足");
            }
        }

        // 判断是否超出用户限量
        if (!empty($productInfo['max_user_buy_num'])) {
            $buyedOrderNum = GroupBuyOrder::find()->where(['uid' => $userInfo['uid'], 'product_id' => $productId, 'status' => [self::ORDER_PAY_SUCCESS, self::ORDER_FINISH, self::ORDER_REFUND_APPLY, self::ORDER_REFUND_PROCESS]])->sum("order_num");
            if ($buyedOrderNum > $productInfo['max_user_buy_num'] || $productInfo['max_user_buy_num'] < $buyedOrderNum + $orderNum) {
                return ToolsHelper::funcReturn("每人限购" . $productInfo['max_user_buy_num'] . "份");
            }
        }

        // 分销商uid
        $dspUid = 0;
        if (!empty($inviteCode)) {
            $dspUid = GroupBuyDsp::find()->select(['uid'])->where(['invite_code' => $inviteCode])->scalar();
        }

        // 订单数据
        $picsArr = json_decode($productInfo['pics'], true);
        $productCover = isset($picsArr[0]) ? $picsArr[0] : '';
        $orderAmount = sprintf("%2.f", $productOptionInfo['price'] * $orderNum);
        $commissionAmount = $commissionRate = 0;
        if (!empty($productInfo['is_distribute'])) {
            $commissionAmount = sprintf("%2.f", $orderAmount * $productInfo['commission_rate']);
            $commissionRate = $productInfo['commission_rate'];
        }


        // 商家佣金比例，优先使用商品设置的商家佣金
        $shopCommissionRate = $productInfo['shop_commission_rate'];
        if (empty($shopCommissionRate)) {
            $shopCommissionRate = GroupBuyShop::find()->select(['commission_rate'])->where(['id' => $productInfo['shop_id']])->scalar();
            if (empty($shopCommissionRate)) {
                $shopCommissionRate = 0;
            }
        }
        $shopOrderAmount = $orderAmount - sprintf("%2.f", $orderAmount * $shopCommissionRate); // 商家订单金额

        // 生成商户订单号
        $orderNo = ToolsHelper::buildTradeNo($userInfo['uid'], 1);
        // 支付参数
        $payParams = [
            'price' => intval($orderAmount * 100), // 转为分
            'desc' => $productInfo['title'] . "【" . $productOptionInfo['name'] . "】",
            'order_no' => $orderNo,
            'openid' => $userInfo['wx_openid'],
            'time_expire' => str_replace(" ", "T", date("Y-m-d H:i:sP", strtotime("+30 minute"))),
            'attach' => [
                'uid' => $userInfo['uid'],
                'orderType' => 1,
            ],
        ];
        $wechatPayService = new WechatPayService();
        $data = $wechatPayService->orderPay($payParams);
        if (!empty($data)) { // 下单
            $orderData = [
                'order_no' => $orderNo,
                'order_amount' => $orderAmount,
                'shop_commission_rate' => $shopCommissionRate,
                'shop_order_amount' => $shopOrderAmount,
                'order_price' => $productOptionInfo['price'],
                'order_num' => $orderNum,
                'refund_type' => $productInfo['refund_type'],
                'remark' => $remark,
                'product_id' => $productInfo['id'],
                'option_id' => $productOptionInfo['id'],
                'option_name' => $productOptionInfo['name'],
                'product_title' => $productInfo['title'],
                'product_cover' => $productCover,
                'shop_id' => $productInfo['shop_id'],
                'uid' => $userInfo['uid'],
                'is_distribute' => $productInfo['is_distribute'],
                'dsp_uid' => intval($dspUid),
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => SELF::ORDER_PAYING,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            // 写入到订单表
            $orderModel = new GroupBuyOrder();
            $orderModel->loadDefaultValues();
            $orderModel->attributes = $orderData;
            if ($orderModel->save()) {
                return ToolsHelper::funcReturn("下单成功", true, $data);
            }
            return ToolsHelper::funcReturn("下单失败");
        } else {
            return ToolsHelper::funcReturn("订单生成失败");
        }
    }

    /**
     * 继续支付
     *
     * @param $userInfo
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 19:09
     */
    public function orderPayContinue($userInfo, $orderId)
    {
        $now = time();
        $uid = $userInfo['uid'];

        $orderInfo = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderInfo) || $orderInfo['uid'] != $uid || $orderInfo['status'] != self::ORDER_PAYING) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $productInfo = GroupBuyProduct::find()->where(['id' => $orderInfo['product_id']])->asArray()->one();
        if (empty($productInfo)) {
            return ToolsHelper::funcReturn("非法操作");
        }

        // 判断是否超出剩余份数、活动是否为进行中
        if ($productInfo['status'] != self::PRODUCT_PASS_STATUS) {
            return ToolsHelper::funcReturn("活动已结束");
        }

        if (!empty($productInfo['start_at']) && $productInfo['start_at'] > $now) {
            return ToolsHelper::funcReturn("活动未开始");
        }

        if (!empty($productInfo['end_at']) && $productInfo['end_at'] < $now) {
            return ToolsHelper::funcReturn("活动已结束");
        }

        // 查找商品购买选项对应的数据
        $productOptionInfo = GroupBuyProductOption::find()->where(['id' => $orderInfo['option_id']])->asArray()->one();
        if (empty($productOptionInfo) || $productOptionInfo['product_id'] != $orderInfo['product_id']) {
            return ToolsHelper::funcReturn("非法操作");
        }

        if ($productOptionInfo['status'] != 1) {
            return ToolsHelper::funcReturn("当前购买项已停售");
        }

        // 判断是否超出活动限量
        if (!empty($productOptionInfo['max_num'])) {
            if ($productOptionInfo['max_num'] <= $productOptionInfo['sale_num'] || $productOptionInfo['max_num'] < $productOptionInfo['sale_num'] + $orderInfo['order_num']) {
                return ToolsHelper::funcReturn("剩余份数不足");
            }
        }

        // 判断是否超出用户限量
        if (!empty($productInfo['max_user_buy_num'])) {
            $buyedOrderNum = GroupBuyOrder::find()->where(['uid' => $uid, 'product_id' => $orderInfo['product_id'], 'status' => [self::ORDER_PAY_SUCCESS, self::ORDER_FINISH, self::ORDER_REFUND_APPLY, self::ORDER_REFUND_PROCESS]])->sum("order_num");
            if ($buyedOrderNum > $productInfo['max_user_buy_num'] || $productInfo['max_user_buy_num'] < $buyedOrderNum + $orderInfo['order_num']) {
                return ToolsHelper::funcReturn("每人限购" . $productInfo['max_user_buy_num'] . "份");
            }
        }

        // 使用第一次下单的prepay_id继续支付
        $wechatPayService = new WechatPayService();
        $prepayId = $wechatPayService->getPrepayIdFromRedis($orderInfo['order_no']);
        if (empty($prepayId)) {
            return ToolsHelper::funcReturn("订单已过期失效，请重新下单再发起支付");
        }

        $data = $wechatPayService->getWechatPayment($prepayId);
        if (!empty($data)) { // 下单
            return ToolsHelper::funcReturn("下单成功", true, $data);
        }
        return ToolsHelper::funcReturn("下单失败");
    }


    /**
     * 支付成功回调
     *
     * @param $uid
     * @param $orderNo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/23 15:13
     */
    public function paySuccess($uid, $orderNo)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['order_no' => $orderNo])->asArray()->one();
        if (empty($orderData) || $orderData['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }
        if ($orderData['status'] != self::ORDER_PAYING) {
            return ToolsHelper::funcReturn("订单已处理完成");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 更新订单状态为支付成功
            $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_PAY_SUCCESS, 'pay_at' => $now, 'updated_at' => $now], ['id' => $orderData['id']]);

            // 更改活动商品表（已售份数）
            $productRes = GroupBuyProduct::updateAllCounters(['sale_num' => $orderData['order_num']], ['id' => $orderData['product_id']]);

            // 更改商品购买选项表（已售份数）
            $productOptionRes = GroupBuyProductOption::updateAllCounters(['sale_num' => $orderData['order_num']], ['id' => $orderData['option_id']]);

            // 更改商家表（预估总收入、有效订单数）
            $shopRes = GroupBuyShop::updateAllCounters(['total_income' => $orderData['shop_order_amount'], 'order_num' => $orderData['order_num']], ['id' => $orderData['shop_id']]);

            // 若为分销商品，判断用户是否为审核通过的分销商，分配分销商佣金
            $dspRes = 1;
            if (!empty($orderData['dsp_uid']) && !empty($orderData['is_distribute'])) {
                $dspInfo = GroupBuyDsp::find()->where(['uid' => $orderData['dsp_uid']])->asArray()->one();
                if (!empty($dspInfo) && $dspInfo['status'] == self::DSP_PASS_STATUS) {
                    $dspRes = GroupBuyDsp::updateAllCounters(['total_income' => $orderData['commission_amount'], 'order_num' => $orderData['order_num']], ['uid' => $orderData['dsp_uid']]);
                }
            }
            if ($orderRes && $productRes && $productOptionRes && $shopRes && $dspRes) {
                $transaction->commit();
                return ToolsHelper::funcReturn("支付成功", true);
            }
            $transaction->rollBack();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info(['func_name' => 'GroupBuyService.paySuccess', 'message' => $e->getMessage()], 'trace');
        }
        return ToolsHelper::funcReturn("支付失败");
    }

    /**
     * 订单列表
     *
     * @param        $uid
     * @param int    $type // 0 用户订单 1 分销佣金订单 2 商家订单
     * @param string $keyword
     * @param int    $page
     * @param int    $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/16 18:57
     */
    public function getOrderList($uid, $type = 0, $keyword = "", $page = 1, $pageSize = 20)
    {
        $now = time();
        $query = GroupBuyOrder::find()->filterWhere(['LIKE', 'product_title', $keyword]);
        switch ($type) {
            case 0:
                $query->andWhere(['uid' => $uid, 'delete_status' => self::ORDER_NO_DELETE]);
                break;
            case 1:
                $query->andWhere(['dsp_uid' => $uid]);
                break;
            case 2:
                $shopId = GroupBuyShopManager::find()->select(['shop_id'])->where(['uid' => $uid, 'status' => 1])->scalar();
                if (empty($shopId)) {
                    return [];
                }
                $query->andWhere(['shop_id' => $shopId]);
                break;
        }
        $start = ($page - 1) * $pageSize;
        $orderList = $query->offset($start)->limit($pageSize)->orderBy("id DESC")->asArray()->all();
        if (!empty($orderList)) {
            $groupBuyOrderStatus = Yii::$app->params['groupBuyOrderStatus'];
            $shopIdArr = ArrayHelper::getColumn($orderList, 'shop_id');
            $shopDataArr = [];
            if (!empty($shopIdArr)) {
                $shopDataArr = GroupBuyShop::find()->select(['id', 'shop_avatar', 'shop_name'])->where(['id' => $shopIdArr])->indexBy("id")->asArray()->all();
            }
            $expireOrderIdArr = [];
            foreach ($orderList as $key => &$value) {
                if ($value['status'] == self::ORDER_PAYING && $now - $value['created_at'] > 1800) {
                    $expireOrderIdArr[] = $value['id'];
                    $value['status'] = self::ORDER_CANCEL;
                }
                $value['refund_type_name'] = Yii::$app->params['groupBuyRefundType'][$value['refund_type']];
                $value['created_at'] = date("Y-m-d H:i:s", $value['created_at']);
                $value['product_cover'] = ToolsHelper::getLocalImg($value['product_cover'], '', 240);
                $value['shop_name'] = isset($shopDataArr[$value['shop_id']]['shop_name']) ? $shopDataArr[$value['shop_id']]['shop_name'] : '';
                $value['shop_avatar'] = isset($shopDataArr[$value['shop_id']]['shop_avatar']) ? ToolsHelper::getLocalImg($shopDataArr[$value['shop_id']]['shop_avatar'], '', 240) : '';
                $value['status_name'] = isset($groupBuyOrderStatus[$value['status']]) ? $groupBuyOrderStatus[$value['status']] : '';
                $value['amount'] = $type == 2 ? $value['shop_order_amount'] : ($type == 1 ? $value['commission_amount'] : $value['order_amount']);
            }
            // 对未付款且下单时间超过30分钟的订单更改状态为已取消
            if (!empty($expireOrderIdArr)) {
                GroupBuyOrder::updateAll(['status' => self::ORDER_CANCEL, 'updated_at' => $now], ['id' => $expireOrderIdArr]);
            }
        }
        return $orderList;
    }

    /**
     * 订单详情
     *
     * @param $uid
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/23 22:04
     */
    public function getOrderInfo($uid, $orderId)
    {
        $orderInfo = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderInfo) || $orderInfo['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }
        $groupBuyOrderStatus = Yii::$app->params['groupBuyOrderStatus'];

        $shopData = GroupBuyShop::find()->select(['shop_name', 'shop_avatar'])->where(['id' => $orderInfo['shop_id']])->asArray()->one();

        $orderInfo['product_cover'] = ToolsHelper::getLocalImg($orderInfo['product_cover'], '', 240);
        $orderInfo['shop_name'] = isset($shopData['shop_name']) ? $shopData['shop_name'] : '';
        $orderInfo['shop_avatar'] = isset($shopData['shop_avatar']) ? ToolsHelper::getLocalImg($shopData['shop_avatar'], '', 240) : '';
        $orderInfo['status_name'] = isset($groupBuyOrderStatus[$orderInfo['status']]) ? $groupBuyOrderStatus[$orderInfo['status']] : '';
        $orderInfo['pay_at'] = !empty($orderInfo['pay_at']) ? date("Y-m-d H:i:s", $orderInfo['pay_at']) : '';
        $orderInfo['finish_at'] = !empty($orderInfo['finish_at']) ? date("Y-m-d H:i:s", $orderInfo['finish_at']) : '';
        $orderInfo['refund_apply_at'] = !empty($orderInfo['refund_apply_at']) ? date("Y-m-d H:i:s", $orderInfo['refund_apply_at']) : '';
        $orderInfo['refund_finish_at'] = !empty($orderInfo['refund_finish_at']) ? date("Y-m-d H:i:s", $orderInfo['refund_finish_at']) : '';
        $orderInfo['refund_type_name'] = Yii::$app->params['groupBuyRefundType'][$orderInfo['refund_type']];
        $orderInfo['created_at'] = !empty($orderInfo['created_at']) ? date("Y-m-d H:i:s", $orderInfo['created_at']) : '';

        return ToolsHelper::funcReturn(
            "订单详情",
            true,
            [
                'orderInfo' => $orderInfo,
            ]
        );
    }

    /**
     * 订单核验地址
     *
     * @param $orderId
     * @param $sign
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/11/24 14:47
     */
    public function getOrderCheckUrl($orderId, $sign)
    {
        $orderInfo = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderInfo)) {
            return Yii::$app->params['domain'] . "/order/erweima";
        }

        // 验证签名
        if (ToolsHelper::getOrderSign($orderInfo['uid'], $orderId) != $sign) {
            return Yii::$app->params['domain'] . "/order/erweima";
        }

        $now = time();
        $secretKey = ToolsHelper::getOrderCheckSecretKey($orderId, $now);

        return Yii::$app->params['domain'] . "/order/erweima?timestamp=" . $now . "&id=" . $orderId . "&secret_key=" . $secretKey;
    }

    /**
     * 核验订单详情
     *
     * @param $uid
     * @param $orderId
     * @param $timestamp
     * @param $secretKey
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 15:37
     */
    public function getOrderCheckInfo($uid, $orderId, $timestamp, $secretKey)
    {
        $now = time();

        // 十分钟内有效
        if ($now - $timestamp > 600) {
            return ToolsHelper::funcReturn("二维码已失效，请重新获取");
        }

        // 验证密钥是否正确
        if (ToolsHelper::getOrderCheckSecretKey($orderId, $timestamp) != $secretKey) {
            return ToolsHelper::funcReturn("二维码非法");
        }

        // 订单数据
        $orderInfo = GroupBuyOrder::find()->where(['id' => $orderId, 'status' => self::ORDER_PAY_SUCCESS])->asArray()->one();
        if (empty($orderInfo)) {
            return ToolsHelper::funcReturn("订单异常");
        }

        // 判断扫描者是否为该店铺管理员
        $manageInfo = $this->getShopManagerByUid($uid);
        if ($manageInfo['shop_id'] != $orderInfo['shop_id']) {
            return ToolsHelper::funcReturn("请确认商家帐号状态", false, ['uid' => $uid]);
        }

        return $this->getOrderInfo($orderInfo['uid'], $orderId);
    }


    /**
     * 订单核销
     *
     * @param $uid
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 21:51
     */
    public function orderFinish($uid, $orderId)
    {
        $now = time();
        // 判断操作者是否为店铺管理员
        $shopId = GroupBuyShopManager::find()->select(['shop_id'])->where(['uid' => $uid, 'status' => 1])->scalar();
        if (empty($shopId)) {
            return ToolsHelper::funcReturn("非商家用户");
        }
        $orderData = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if ($orderData['shop_id'] != $shopId && $orderData['status'] != self::ORDER_PAY_SUCCESS) {
            return ToolsHelper::funcReturn("订单异常");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 更新订单状态
            $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_FINISH, 'finish_uid' => $uid, 'finish_at' => $now, 'updated_at' => $now], ['id' => $orderId]);

            // 更新商家结算金额
            $shopRes = GroupBuyShop::updateAllCounters(['settle_amount' => $orderData['shop_order_amount']], ['id' => $shopId]);

            // 更新分销商佣金
            $dspRes = 1;
            if (!empty($orderData['dsp_uid'])) {
                $dspRes = GroupBuyDsp::updateAllCounters(['settle_amount' => $orderData['commission_amount']], ['uid' => $orderData['dsp_uid']]);
            }

            if ($orderRes && $shopRes && $dspRes) {
                $transaction->commit();
                return ToolsHelper::funcReturn("核销成功", true);
            }
            $transaction->rollBack();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info(['func_name' => 'GroupBuyService.orderFinish', 'message' => $e->getMessage()], 'trace');
        }
        return ToolsHelper::funcReturn("核销失败");
    }


    /**
     * 删除订单
     *
     * @param $uid
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 18:35
     */
    public function orderDelete($uid, $orderId)
    {
        $now = time();
        $orderModel = GroupBuyOrder::find()->where(['id' => $orderId])->one();
        if (empty($orderModel)) {
            return ToolsHelper::funcReturn("订单不存在");
        }
        // 验证用户是否合法
        if ($orderModel['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }
        // 验证订单状态
        if (in_array($orderModel['status'], [self::ORDER_PAY_SUCCESS, self::ORDER_REFUND_APPLY, self::ORDER_REFUND_PROCESS])) {
            return ToolsHelper::funcReturn("该订单状态不可删除");
        }

        $orderModel->delete_status = self::ORDER_DELETE;
        $orderModel->delete_at = $now;
        $orderModel->updated_at = $now;
        if ($orderModel->save()) {
            return ToolsHelper::funcReturn("删除成功", true);
        }
        return ToolsHelper::funcReturn("删除失败");
    }


    /**
     * 申请退款
     *
     * @param $uid
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 22:01
     */
    public function orderApplyRefund($uid, $orderId)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderData) || $orderData['uid'] != $uid || !in_array($orderData['status'], [self::ORDER_PAY_SUCCESS, self::ORDER_REFUND_FAIL]) || $orderData['refund_type'] != self::REFUND_SUPPORT) {
            return ToolsHelper::funcReturn("非法操作");
        }

        // 更改订单状态
        $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_REFUND_APPLY, 'refund_amount' => $orderData['order_amount'], 'refund_apply_at' => $now, 'refund_fail_reason' => '', 'updated_at' => $now], ['id' => $orderId]);
        if ($orderRes) {
            return ToolsHelper::funcReturn("申请退款成功", true);
        }
        return ToolsHelper::funcReturn("申请退款失败");
    }

    /**
     * 取消退款申请
     *
     * @param $uid
     * @param $orderId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 22:38
     */
    public function cancelOrderApplyRefund($uid, $orderId)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['id' => $orderId])->asArray()->one();
        if (empty($orderData) || $orderData['uid'] != $uid || $orderData['status'] != self::ORDER_REFUND_APPLY || $orderData['refund_type'] != self::REFUND_SUPPORT) {
            return ToolsHelper::funcReturn("非法操作");
        }

        // 更改订单状态
        $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_PAY_SUCCESS, 'refund_amount' => 0, 'refund_apply_at' => 0, 'updated_at' => $now], ['id' => $orderId]);
        if ($orderRes) {
            return ToolsHelper::funcReturn("取消申请退款成功", true);
        }
        return ToolsHelper::funcReturn("取消申请退款失败");
    }


    /**
     * 退款成功回调
     *
     * @param $orderNo
     * @param $refundNo
     * @param $successTime
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/25 16:28
     */
    public function refundSuccess($orderNo, $refundNo, $successTime)
    {
        $orderData = GroupBuyOrder::find()->where(['order_no' => $orderNo])->asArray()->one();
        if (empty($orderData) || $orderData['status'] != self::ORDER_REFUND_PROCESS) {
            return ToolsHelper::funcReturn("订单不是退款处理中");
        }

        $now = time();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 更新订单状态
            $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_REFUND_SUCCESS, 'refund_order_no' => $refundNo, 'refund_fail_reason' => '', 'refund_finish_at' => $successTime, 'updated_at' => $now], ['id' => $orderData['id']]);

            // 更改活动商品表（已售份数）
            $productRes = GroupBuyProduct::updateAllCounters(['sale_num' => -$orderData['order_num']], ['id' => $orderData['product_id']]);

            // 更改商品购买选项表（已售份数）
            $productOptionRes = GroupBuyProductOption::updateAllCounters(['sale_num' => -$orderData['order_num']], ['id' => $orderData['option_id']]);

            // 更改商家表（预估总收入、有效订单数）
            $shopRes = GroupBuyShop::updateAllCounters(['total_income' => -$orderData['shop_order_amount'], 'order_num' => -$orderData['order_num']], ['id' => $orderData['shop_id']]);

            // 若为分销订单，更改分销商佣金
            $dspRes = 1;
            if (!empty($orderData['dsp_uid'])) {
                $dspRes = GroupBuyDsp::updateAllCounters(['total_income' => -$orderData['commission_amount'], 'order_num' => -$orderData['order_num']], ['uid' => $orderData['dsp_uid']]);
            }
            if ($orderRes && $productRes && $productOptionRes && $shopRes && $dspRes) {
                $transaction->commit();
                return ToolsHelper::funcReturn("退款成功", true);
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info(['func_name' => 'GroupBuyService.orderRefund', 'message' => $e->getMessage()], 'trace');
        }
        return ToolsHelper::funcReturn("退款失败");
    }

    /**
     * 退款失败回调
     *
     * @param $orderNo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/25 14:12
     */
    public function refundFail($orderNo)
    {
        $now = time();
        $orderData = GroupBuyOrder::find()->where(['order_no' => $orderNo])->asArray()->one();
        if (empty($orderData) || $orderData['status'] != self::ORDER_REFUND_PROCESS) {
            return ToolsHelper::funcReturn("订单不是退款处理中");
        }

        $orderRes = GroupBuyOrder::updateAll(['status' => self::ORDER_REFUND_FAIL, 'refund_fail_reason' => '退款异常，请联系客服处理', 'updated_at' => $now], ['id' => $orderData['id']]);
        if ($orderRes) {
            return ToolsHelper::funcReturn("退款失败处理成功");
        }
        return ToolsHelper::funcReturn("退款失败处理失败");
    }


    /**
     * 根据用户uid查找店铺管理员
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/17 12:09
     */
    public function getShopManagerByUid($uid)
    {
        return GroupBuyShopManager::find()->where(['uid' => $uid, 'status' => 1])->asArray()->one();
    }

    /**
     * 用户分销信息
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/22 22:11
     */
    public function getDspInfo($uid)
    {
        $dspInfo = [];
        if (!empty($uid)) {
            $dspInfo = GroupBuyDsp::find()->where(['uid' => $uid])->asArray()->one();
            $groupBuyDspStatus = Yii::$app->params['groupBuyDspStatus'];
            $dspInfo['status_name'] = isset($groupBuyDspStatus[$dspInfo['status']]) ? $groupBuyDspStatus[$dspInfo['status']] : '未申请';
        }
        return $dspInfo;
    }

    /**
     * 申请dsp分销用户
     *
     * @param $userInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/17 15:23
     */
    public function dspApply($userInfo)
    {
        $uid = $userInfo['uid'];

        // 判断是否为店铺管理员
        $managerInfo = $this->getShopManagerByUid($uid);
        if (!empty($managerInfo)) {
            return ToolsHelper::funcReturn("商家不可成为分销用户");
        }

        $now = time();
        $dspModel = GroupBuyDsp::find()->where(['uid' => $uid])->one();
        if (empty($dspModel)) {
            $dspModel = new GroupBuyDsp();
            $dspModel->loadDefaultValues();
            $dspModel->uid = $uid;
            $dspModel->invite_code = $userInfo['invite_code'];
            $dspModel->created_at = $now;
        }
        if ($dspModel->status == self::DSP_PASS_STATUS) {
            return ToolsHelper::funcReturn("您已申请通过");
        }
        $dspModel->audit_reason = "";
        $dspModel->status = self::DSP_AUDITING_STATUS;
        $dspModel->updated_at = $now;
        if ($dspModel->save()) {
            return ToolsHelper::funcReturn("申请成功", true);
        }
        return ToolsHelper::funcReturn("申请失败");
    }

    /**
     * 店铺详情
     *
     * @param $shopId
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/20 11:11
     */
    public function getShopInfo($shopId)
    {
        $shopInfo = GroupBuyShop::find()->where(['id' => $shopId])->asArray()->one();
        if (empty($shopInfo)) {
            return [];
        }
        $shopInfo['shop_avatar'] = ToolsHelper::getLocalImg($shopInfo['shop_avatar'], '', 240);

        // banner
        $picList = json_decode($shopInfo['pics'], true);
        $bannerList = [];
        foreach ($picList as $k => $imgUrl) {
            $imageUrl = ToolsHelper::getLocalImg($imgUrl, '', 540);
            $bannerList[] = [
                'app_id' => '',
                'link_url' => '',
                'image_url' => $imageUrl,
            ];
        }
        $shopInfo['pics'] = $bannerList;

        return $shopInfo;
    }

    /**
     * 收益概览
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/26 16:28
     */
    public function getOverviewData($uid)
    {
        $yesterdayEnd = strtotime(date("Y-m-d"));
        $yesterdayStart = $yesterdayEnd - 86400;

        $shopId = GroupBuyShopManager::find()->select(['shop_id'])->where(['uid' => $uid, 'status' => 1])->scalar();
        if (!empty($shopId)) {
            $data = GroupBuyShop::find()->where(['id' => $shopId])->asArray()->one();
            if (!empty($data)) {
                $data['join_date'] = $data['status'] == 1 ? date("Y-m-d", $data['created_at']) : '';
                $data['role_name'] = "商家";
                $data['withdraw_avariable_amount'] = sprintf("%.2f", $data['settle_amount'] - $data['withdraw_amount']);
                $yesterdayIncome = GroupBuyOrder::find()->where(['shop_id' => $shopId, 'status' => [self::ORDER_PAY_SUCCESS, self::ORDER_FINISH]])->andWhere(['>=', 'pay_at', $yesterdayStart])->andWhere(['<', 'pay_at', $yesterdayEnd])->sum("shop_order_amount");
                $data['yesterday_income'] = !empty($yesterdayIncome) ? $yesterdayIncome : '0.00';
                return $data;
            }
        }

        // 查找是不是分销用户
        $data = GroupBuyDsp::find()->where(['uid' => $uid])->asArray()->one();
        if (!empty($data)) {
            $data['join_date'] = $data['status'] == self::DSP_PASS_STATUS ? date("Y-m-d", $data['open_at']) : '';
            $groupBuyDspStatus = Yii::$app->params['groupBuyDspStatus'];
            $data['role_name'] = isset($groupBuyDspStatus[$data['status']]) ? $groupBuyDspStatus[$data['status']] : '未申请';
            $data['withdraw_avariable_amount'] = sprintf("%.2f", $data['settle_amount'] - $data['withdraw_amount']);
            $yesterdayIncome = GroupBuyOrder::find()->where(['dsp_uid' => $uid, 'status' => [self::ORDER_PAY_SUCCESS, self::ORDER_FINISH]])->andWhere(['>=', 'pay_at', $yesterdayStart])->andWhere(['<', 'pay_at', $yesterdayEnd])->sum("commission_amount");
            $data['yesterday_income'] = !empty($yesterdayIncome) ? $yesterdayIncome : 0.00;
            return $data;
        }

        return [
            'join_date' => '',
            'role_name' => '',
            'total_income' => '0.00',
            'order_num' => 0,
            'withdraw_amount' => '0.00',
            'withdraw_avariable_amount' => '0.00',
            'yesterday_income' => '0.00',
            'settle_amount' => '0.00',
        ];
    }

    /**
     * 店铺商品列表
     *
     * @param     $shopId
     * @param int $status
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/20 11:32
     */
    public function getShopProductList($shopId, $status = 1, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        $productList = GroupBuyProduct::find()->where(['shop_id' => $shopId, 'status' => $status])->offset($start)->limit($pageSize)->orderBy(['sort' => 'ASC', 'updated_at' => 'DESC'])->asArray()->all();
        if (!empty($productList)) {
            foreach ($productList as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $value['cover'] = ToolsHelper::getLocalImg($picList[0], '', 540);
                // 分销佣金
                $value['commission'] = 0;
                if ($value['is_distribute']) {
                    $value['commission'] = round($value['price'] * $value['commission_rate'], 2);
                }
            }
        }
        return $productList;
    }


    /**
     * 提现页面
     *
     * @param $userInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/28 11:09
     */
    public function getWithdrawData($userInfo)
    {
        $uid = $userInfo['uid'];
        $groupBuyWithdrawList = Yii::$app->params['groupBuyWithdrawList'];
        $overviewData = $this->getOverviewData($uid);
        if ($overviewData['role_name'] == "商家") {
            $withdrawList = $groupBuyWithdrawList[2];
        } else {
            $withdrawList = $groupBuyWithdrawList[1];
        }

        if (!empty($withdrawList)) {
            foreach ($withdrawList as $key => $value) {
                if ($value['status'] != 1) {
                    unset($withdrawList[$key]);
                }
            }
        }

        $ruleList = '<p class="rule-item">1、目前提现尚未开通线上打款业务（后期会开通），将以微信转账的方式支付到账；</p>
                     <p class="rule-item">2、请在我的 - 设置（图标）- 手机号，进行手机号认证，以便联系并进行打款；</p>
                     <p class="rule-item">3、推荐关联公众号，平台通知将通过公众号发送消息通知；</p>';

        return ToolsHelper::funcReturn(
            "提现数据",
            true,
            [
                'userInfo' => $userInfo,
                'withdrawList' => array_values($withdrawList),
                'overviewData' => $overviewData,
                'ruleList' => $ruleList,
            ]
        );
    }

    /**
     * dsp用户申请提现
     *
     * @param $uid
     * @param $amount
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/17 12:03
     */
    public function dspWithdrawApply($uid, $amount)
    {
        if ($amount == 0) {
            return ToolsHelper::funcReturn("非法操作");
        }
        $now = time();
        $dspData = GroupBuyDsp::find()->where(['uid' => $uid])->asArray()->one();
        if (empty($dspData)) {
            return ToolsHelper::funcReturn("非分销店主");
        }
        if ($dspData['status'] != self::DSP_PASS_STATUS) {
            return ToolsHelper::funcReturn("非分销店主");
        }
        $currentAmount = $dspData['settle_amount'] - $amount;
        if ($currentAmount < 0) {
            return ToolsHelper::funcReturn("可提现余额不足");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 写入申请表
            $withdrawData = [
                'uid' => $uid,
                'amount' => $amount,
                'current_amount' => $currentAmount,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $groupBuyWithdraw = new GroupBuyWithdraw();
            $groupBuyWithdraw->loadDefaultValues();
            $groupBuyWithdraw->attributes = $withdrawData;
            $withdrawRes = $groupBuyWithdraw->save();

            // 更新dsp的结算余额
            $dspRes = GroupBuyDsp::updateAllCounters(['withdraw_amount' => $amount], ['id' => $dspData['id']]);

            if ($withdrawRes && $dspRes) {
                $transaction->commit();
                return ToolsHelper::funcReturn("申请提现成功", true);
            }
            $transaction->rollBack();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info(['func_name' => 'GroupBuyService.dspWithdrawApply', 'message' => $e->getMessage()], 'trace');
        }
        return ToolsHelper::funcReturn("申请提现失败");
    }

    /**
     * 店铺提现申请
     *
     * @param $uid
     * @param $shopId
     * @param $amount
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/17 12:14
     */
    public function shopWithdrawApply($uid, $shopId, $amount)
    {
        if ($amount == 0) {
            return ToolsHelper::funcReturn("非法操作");
        }
        $now = time();
        $shopData = GroupBuyShop::find()->where(['id' => $shopId])->asArray()->one();
        if (empty($shopData)) {
            return ToolsHelper::funcReturn("非商家用户");
        }
        $currentAmount = $shopData['settle_amount'] - $amount;
        if ($currentAmount < 0) {
            return ToolsHelper::funcReturn("可提现余额不足");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 写入申请表
            $withdrawData = [
                'uid' => $uid,
                'shop_id' => $shopId,
                'amount' => $amount,
                'current_amount' => $currentAmount,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $groupBuyWithdraw = new GroupBuyWithdraw();
            $groupBuyWithdraw->loadDefaultValues();
            $groupBuyWithdraw->attributes = $withdrawData;
            $withdrawRes = $groupBuyWithdraw->save();

            // 更新dsp的结算余额
            $shopRes = GroupBuyShop::updateAllCounters(['withdraw_amount' => $amount], ['id' => $shopData['id']]);

            if ($withdrawRes && $shopRes) {
                $transaction->commit();
                return ToolsHelper::funcReturn("申请提现成功", true);
            }
            $transaction->rollBack();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info(['func_name' => 'GroupBuyService.shopWithdrawApply', 'message' => $e->getMessage()], 'trace');
        }
        return ToolsHelper::funcReturn("申请提现失败");
    }

    /**
     * 提现记录
     *
     * @param     $uid
     * @param int $shopId
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/29 11:16
     */
    public function getWithdrawRecordList($uid, $shopId = 0, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        if (empty($shopId)) {
            $recordList = GroupBuyWithdraw::find()->where(['uid' => $uid])->offset($start)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        } else {
            $recordList = GroupBuyWithdraw::find()->where(['shop_id' => $shopId])->offset($start)->limit($pageSize)->orderBy('id desc')->asArray()->all();
        }
        $groupBuyWithdrawStatus = Yii::$app->params['groupBuyWithdrawStatus'];
        if (!empty($recordList)) {
            foreach ($recordList as $key => &$value) {
                $value['created_at'] = date("Y-m-d H:i:s", $value['created_at']);
                $value['status_name'] = isset($groupBuyWithdrawStatus[$value['status']]) ? $groupBuyWithdrawStatus[$value['status']] : '';
            }
        }
        return $recordList;
    }


}