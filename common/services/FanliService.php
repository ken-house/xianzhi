<?php
/**
 * 返利服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/13 21:54
 */

namespace common\services;

use common\helpers\ToolsHelper;
use common\models\BusinessProductList;
use common\models\Order;
use common\models\OrderApply;
use console\services\jobs\MessageJob;
use Yii;

class FanliService
{
    /**
     * 申请返利
     *
     * @param        $uid
     * @param string $orderSn
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/13 23:26
     */
    public function orderApply($uid, $orderSn = '')
    {
        try {
            $now = time();
            // 检测订单是否存在
            $orderData = Order::find()->where(['order_sn' => $orderSn])->asArray()->one();
            if (!empty($orderData)) {
                if (!empty($orderData['uid'])) {
                    return ToolsHelper::funcReturn("该订单号已申请过返利");
                }

                if (!empty($orderData['return_success'])) {
                    return ToolsHelper::funcReturn("该订单号已返利完成");
                }
                // 判断订单状态是否为已结算，若已结算发放积分奖励
                if (($orderData['source_id'] == 1 && $orderData['status'] == 17 && $orderData['settle_date'] < date("Ymd")) || ($orderData['source_id'] == 2 && $orderData['status'] == 5)) {
                    // 发放积分奖励
                    $returnFee = Order::find()->where(['order_sn' => $orderSn])->sum('return_fee'); // 由于订单可以有多笔
                    $rewardPoint = $returnFee * 10000;
                    $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_FANLI_AWARD_TYPE, $uid, $now);
                    $rewardRes = $rewardPointService->awardPoint($rewardPoint);
                    if ($rewardRes['result']) {
                        // 更新订单数据
                        $updateRes = Order::updateAll(['uid' => $uid, 'return_success' => 1, 'updated_at' => $now], ['order_sn' => $orderSn]);
                        if ($updateRes) {
                            // 发送系统消息
                            Yii::$app->messageQueue->push(
                                new MessageJob(
                                    [
                                        'data' => [
                                            [
                                                'userInfo' => [
                                                    'uid' => $uid
                                                ],
                                                'orderSn' => $orderData['order_sn'],
                                                'rewardPoint' => $rewardPoint,
                                                'messageType' => MessageService::SYSTEM_ORDER_FANLI_SUCCESS_MESSAGE
                                            ]
                                        ]
                                    ]
                                )
                            );

                            return ToolsHelper::funcReturn(
                                "返利完成",
                                true,
                                [
                                    'rewardTips' => "返利完成，" . abs($rewardRes['data']['point']) . "积分已放入账户"
                                ]
                            );
                        }
                    }
                } else { // 订单未完成结算
                    $rewardTips = '';
                    if (($orderData['source_id'] == 1 && in_array($orderData['status'], [15, 16, 17, 24])) || ($orderData['source_id'] == 2 && in_array($orderData['status'], [0, 1, 2, 3, 5]))) {
                        // 首单是否任务完成
                        $orderCount = Order::find()->where(['uid' => $uid])->groupBy('order_sn')->count();
                        if ($orderCount == 0) {
                            $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE, $uid, $now);
                            $rewardRes = $rewardPointService->awardPoint();
                            if ($rewardRes['result']) {
                                $rewardTips = "首单任务完成，" . abs($rewardRes['data']['point']) . "积分已放入账户。";
                            }
                        } else { // 每日一单任务-按申请时间来
                            // 下单当日开始时间
                            $orderStartAt = strtotime(date("Y-m-d", $orderData['order_time']));
                            $orderEndAt = $orderStartAt + 86400;
                            $todayOrderCount = Order::find()->where(['uid' => $uid])->andWhere(['>=', 'order_time', $orderStartAt])->andWhere(['<', 'order_time', $orderEndAt])->count();
                            if ($todayOrderCount == 0) {
                                $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_AWARD_TYPE, $uid, $now);
                                $rewardRes = $rewardPointService->onceTaskAwardPoint();
                                if ($rewardRes['result']) {
                                    $rewardTips = $rewardRes['message'] . "。";
                                }
                            }
                        }

                        // 订单数完成奖励
                        $orderNumAwardArr = Yii::$app->params['orderNumAwardArr'];
                        foreach ($orderNumAwardArr as $orderNum => $point) {
                            if ($orderNum == $orderCount + 1) {
                                $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_NUM_AWARD_TYPE, $uid, $now);
                                $rewardRes = $rewardPointService->awardPoint($point);
                                if ($rewardRes['result']) {
                                    $rewardTips .= "完成订单数" . ($orderCount + 1) . "获得" . abs($rewardRes['data']['point']) . "积分已放入账户。";
                                }
                                break;
                            }
                        }
                    }
                    $updateRes = Order::updateAll(['uid' => $uid, 'updated_at' => $now], ['order_sn' => $orderSn]);
                    if ($updateRes) {
                        return ToolsHelper::funcReturn(
                            "申请返利成功",
                            true,
                            [
                                'rewardTips' => $rewardTips . "返利积分到账时间请关注订单列表",
                            ]
                        );
                    }
                }
                Yii::info(['func_name' => 'FanliService.orderApply', 'uid' => $uid, 'order_sn' => $orderSn], 'trace');
                return ToolsHelper::funcReturn("系统错误");
            } else { // 未查询到该笔订单，等待脚本处理
                // 写入订单申请记录表中
                $exist = OrderApply::find()->where(['order_sn' => $orderSn])->exists();
                if ($exist) {
                    return ToolsHelper::funcReturn("不可重复申请");
                }
                $orderApply = new OrderApply();
                $orderApply->uid = $uid;
                $orderApply->order_sn = $orderSn;
                $orderApply->created_at = $now;
                if ($orderApply->save()) {
                    return ToolsHelper::funcReturn(
                        "提交成功，等待系统审核",
                        true,
                        [
                            'rewardTips' => "申请成功，等待系统审核",
                        ]
                    );
                }
                Yii::info(['func_name' => 'FanliService.orderApply', 'uid' => $uid, 'order_sn' => $orderSn], 'trace');
                return ToolsHelper::funcReturn("系统错误");
            }
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'FanliService.orderApply', 'message' => $e->getMessage()], 'trace');
            return ToolsHelper::funcReturn("系统错误");
        }
    }

    /**
     * 订单列表
     *
     * @param $uid
     * @param $keyword
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/10/14 15:32
     */
    public function getOrderList($uid, $keyword, $page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $orderList = Order::find()->where(['uid' => $uid])->andFilterWhere(['LIKE', 'product_name', $keyword])->offset($start)->limit($pageSize)->orderBy('updated_at desc')->asArray()->all();
        if (!empty($orderList)) {
            foreach ($orderList as $key => &$orderInfo) {
                $productInfo = BusinessProductList::find()->where(['business_product_id' => $orderInfo['business_product_id'], 'source_id' => $orderInfo['source_id']])->asArray()->one();
                if (!empty($productInfo)) {
                    $productImageArr = json_decode($productInfo['pics'], true);
                    $productImage = !empty($productImageArr) ? $productImageArr[0] : '';
                } else {
                    $productImage = $orderInfo['product_image'];
                    if (empty($productImage)) {
                        // 调用电商详情接口
                        if ($orderInfo['source_id'] == 1) {
                            $jdService = new JdService();
                            $productData = $jdService->getProductInfo([$orderInfo['business_product_id']]);
                            if (!empty($productData[0]['imageInfo']['imageList'][0]['url'])) {
                                $productImage = $productData[0]['imageInfo']['imageList'][0]['url'];
                                Order::updateAll(['product_image' => $productImage], ['id' => $orderInfo['id']]);
                            }
                        }
                    }
                }
                $orderInfo['order_time'] = date("Y-m-d H:i:s", $orderInfo['order_time']);
                $orderInfo['settle_date'] = !empty($orderInfo['settle_date']) ? date("Y-m-d", strtotime($orderInfo['settle_date'])) : '';
                $orderInfo['click_url'] = isset($productInfo['click_url']) ? $productInfo['click_url'] : '';
                $orderInfo['app_id'] = isset($productInfo['app_id']) ? $productInfo['app_id'] : '';
                $orderInfo['product_image'] = $productImage;
                $orderInfo['cut_price'] = $orderInfo['product_price'] * $orderInfo['product_num'] - $orderInfo['actual_cos_price'];
                $orderInfo['order_status'] = ToolsHelper::getOrderStatus($orderInfo);
                $orderInfo['show_fanli'] = 1;
                if (!(($orderInfo['source_id'] == 1 && in_array($orderInfo['status'], [15, 16, 17, 24])) || ($orderInfo['source_id'] == 2 && in_array($orderInfo['status'], [0, 1, 2, 3, 5])))) {
                    $orderInfo['show_fanli'] = 0;
                }
                $orderInfo['express_status'] = "";
                if ($orderInfo['source_id'] == 1 && $orderInfo['express_status'] > 0) {
                    $orderInfo['express_status'] = $orderInfo['express_status'] == 20 ? '已发货' : '待发货';
                }
                $orderInfo['source_name'] = $orderInfo['source_id'] == 1 ? '京东' : '拼多多';
            }
        }
        return $orderList;
    }

    /**
     * 数据统计（订单数、总金额、总佣金、总节省）
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/10/14 18:37
     */
    public function getStatisticData($uid)
    {
        $dataArr = Order::find()->select(['COUNT(distinct(`order_sn`)) AS order_count', 'SUM(`return_fee`) AS return_total_fee', 'SUM(`actual_cos_price`) AS total_amount', 'SUM(`product_price`*`product_num`-`actual_cos_price`) AS cut_amount'])->where(['uid' => $uid])->asArray()->one();
        $dataArr['order_count'] = intval($dataArr['order_count']);
        $dataArr['return_total_fee'] = floatval($dataArr['return_total_fee']);
        $dataArr['total_amount'] = floatval($dataArr['total_amount']);
        $dataArr['cut_amount'] = floatval($dataArr['cut_amount']) < 0 ? 0 : $dataArr['cut_amount'];
        return $dataArr;
    }
}