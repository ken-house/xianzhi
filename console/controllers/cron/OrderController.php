<?php
/**
 * 电商订单
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/13 13:22
 */

namespace console\controllers\cron;

use common\helpers\ToolsHelper;
use common\models\Order;
use common\models\OrderApply;
use common\services\JdService;
use common\services\MessageService;
use common\services\PddService;
use common\services\RewardPointService;
use common\services\TemplateMsgService;
use console\services\jobs\MessageJob;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class OrderController extends Controller
{
    /**
     * 京东订单
     * 执行时间：每十分钟执行一次
     *
     * @param int $endTime
     *
     * @author     xudt
     * @date-time  2021/10/13 14:13
     */
    public function actionJdOrder($endTime = 0)
    {
        try {
            $now = time();
            if (empty($endTime)) {
                $endTime = time();
            }
            $endTime = date("Y-m-d H:i:s", $endTime);

            $jdService = new JdService();
            $dataList = $jdService->getOrderListByType(1, $endTime);
            if (!empty($dataList)) {
                foreach ($dataList as $key => $value) {
                    $orderId = strval($value['id']); // 订单唯一标识
                    $orderSn = strval($value['orderId']); // 订单号
                    $actualCosPrice = !empty($value['actualCosPrice']) ? $value['actualCosPrice'] : $value['estimateCosPrice']; // 总价（计佣金金额）
                    $actualFee = !empty($value['actualFee']) ? $value['actualFee'] : $value['estimateFee']; // 佣金
                    $orderInfo = [
                        'order_id' => $orderId,
                        'order_sn' => $orderSn,
                        'order_time' => !empty($value['orderTime']) ? strtotime($value['orderTime']) : 0,
                        'finish_time' => !empty($value['finishTime']) ? strtotime($value['finishTime']) : 0,
                        'modify_time' => !empty($value['modifyTime']) ? strtotime($value['modifyTime']) : 0,
                        'settle_date' => intval($value['payMonth']),
                        'business_product_id' => $value['skuId'],
                        'product_name' => $value['skuName'],
                        'product_image' => isset($value['goodsInfo']['imageUrl']) ? $value['goodsInfo']['imageUrl'] : '',
                        'express_status' => $value['expressStatus'],
                        'product_price' => $value['price'],
                        'product_num' => $value['skuNum'],
                        'actual_cos_price' => $actualCosPrice,
                        'actual_fee' => $actualFee,
                        'return_fee' => ToolsHelper::getCashBackPrice($actualFee),
                        'status' => $value['validCode'],
                        'source_id' => 1,
                        'updated_at' => $now,
                    ];

                    // 判断订单是否存在
                    $orderModel = Order::find()->where(['order_id' => $orderId])->one();
                    if (empty($orderModel)) { // 更新
                        $orderInfo['created_at'] = $now;
                        $orderModel = new Order();
                    }
                    $orderModel->attributes = $orderInfo;
                    if (!$orderModel->save()) {
                        Yii::info(['func_name' => 'actionJdOrder', 'orderInfo' => $orderInfo, 'message' => $orderModel->getErrors()], 'trace');
                    }
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'actionJdOrder', 'message' => $e->getMessage()], 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 拼多多
     * 执行时间：每十分钟执行一次
     *
     * @param int $endTime
     *
     * @author     xudt
     * @date-time  2021/10/13 14:37
     */
    public function actionPddOrder($endTime = 0)
    {
        try {
            $now = time();
            if (empty($endTime)) {
                $endTime = time();
            }

            $pddService = new PddService();
            $dataList = $pddService->getOrderListByUpdateTime($endTime);
            if (!empty($dataList)) {
                foreach ($dataList as $key => $value) {
                    $orderId = strval($value['order_id']);
                    $orderSn = strval($value['order_sn']);
                    $actualFee = round($value['promotion_amount'] / 100, 2);
                    $orderInfo = [
                        'order_id' => $orderId,
                        'order_sn' => $orderSn,
                        'order_time' => !empty($value['order_pay_time']) ? $value['order_pay_time'] : 0,
                        'finish_time' => !empty($value['order_receive_time']) ? $value['order_receive_time'] : 0,
                        'modify_time' => !empty($value['order_modify_at']) ? $value['order_modify_at'] : 0,
                        'settle_date' => !empty($value['order_settle_time']) ? date("Ymd", $value['order_settle_time']) : 0,
                        'business_product_id' => $value['goods_id'],
                        'product_name' => $value['goods_name'],
                        'product_image' => $value['goods_thumbnail_url'],
                        'express_status' => 0,
                        'product_price' => round($value['goods_price'] / 100, 2),
                        'product_num' => $value['goods_quantity'],
                        'actual_cos_price' => round($value['order_amount'] / 100, 2),
                        'actual_fee' => $actualFee,
                        'return_fee' => ToolsHelper::getCashBackPrice($actualFee),
                        'status' => $value['order_status'],
                        'source_id' => 2,
                        'updated_at' => $now,
                    ];

                    // 判断订单是否存在
                    $orderModel = Order::find()->where(['order_id' => $orderId])->one();
                    if (empty($orderModel)) { // 更新
                        $orderInfo['created_at'] = $now;
                        $orderModel = new Order();
                    }
                    $orderModel->attributes = $orderInfo;
                    if (!$orderModel->save()) {
                        Yii::info(['actionName' => 'actionPddOrder', 'orderInfo' => $orderInfo, 'message' => $orderModel->getErrors()], 'trace');
                    }
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info(['actionName' => 'actionPddOrder', 'message' => $e->getMessage()], 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }


    /**
     * 申请返利，订单关联用户
     * 每2分钟执行一次
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/18 21:50
     */
    public function actionApply()
    {
        try {
            $now = time();
            // 读取OrderReply表中有效记录
            $orderApplyList = OrderApply::find()->asArray()->all();
            if (!empty($orderApplyList)) {
                foreach ($orderApplyList as $key => $value) {
                    $orderApplyId = $value['id'];
                    $orderSn = $value['order_sn'];
                    $uid = $value['uid'];

                    // 查询订单详情
                    $orderData = Order::find()->where(['order_sn' => $orderSn])->asArray()->one();
                    // 未找到该订单，则跳过
                    if (empty($orderData)) {
                        continue;
                    }

                    // 已返利
                    if (!empty($orderData['return_success']) || !empty($orderData['uid'])) {
                        OrderApply::deleteAll(['id' => $orderApplyId]);
                        continue;
                    }

                    // 申请返利绑定用户关系
                    $updateRes = Order::updateAll(['uid' => $uid, 'updated_at' => $now], ['order_sn' => $orderSn]);
                    if (!$updateRes) {
                        Yii::info(['actionName' => 'actionApply', 'orderData' => $orderData], 'trace');
                        continue;
                    }

                    // 删除申请记录
                    OrderApply::deleteAll(['id' => $orderApplyId]);

                    // 若订单为取消状态则跳过
                    if (!(($orderData['source_id'] == 1 && in_array($orderData['status'], [15, 16, 17, 24])) || ($orderData['source_id'] == 2 && in_array($orderData['status'], [0, 1, 2, 3, 5])))) {
                        continue;
                    }

                    // 检查是否完成首次下单任务或每日一单任务
                    $rewardPoint = $taskId = 0;
                    // 首单是否任务完成
                    $orderCount = Order::find()->where(['uid' => $uid])->andWhere(['<>', 'order_sn', $orderSn])->groupBy('order_sn')->count();
                    if ($orderCount == 0) {
                        $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE, $uid, $now);
                        $rewardRes = $rewardPointService->awardPoint();
                        if ($rewardRes['result']) {
                            $rewardPoint = $rewardRes['data']['point'];
                            $taskId = RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE;
                        }
                    } else { // 每日一单任务
                        // 下单当日开始时间
                        $orderStartAt = strtotime(date("Y-m-d", $orderData['order_time']));
                        $orderEndAt = $orderStartAt + 86400;
                        $todayOrderCount = Order::find()->where(['uid' => $uid])->andWhere(['<>', 'order_sn', $orderSn])->andWhere(['>=', 'order_time', $orderStartAt])->andWhere(['<', 'order_time', $orderEndAt])->count();
                        if ($todayOrderCount == 0) {
                            $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_AWARD_TYPE, $uid, $now);
                            $rewardRes = $rewardPointService->onceTaskAwardPoint();
                            if ($rewardRes['result']) {
                                $rewardPoint = $rewardRes['data']['point'];
                                $taskId = RewardPointService::BUSINESS_ORDER_AWARD_TYPE;
                            }
                        }
                    }

                    // 订单数完成奖励
                    $orderNumTask = [];
                    $orderNumAwardArr = Yii::$app->params['orderNumAwardArr'];
                    foreach ($orderNumAwardArr as $orderNum => $point) {
                        if ($orderNum == $orderCount + 1) {
                            $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_NUM_AWARD_TYPE, $uid, $now);
                            $rewardRes = $rewardPointService->awardPoint($point);
                            if ($rewardRes['result']) {
                                $orderNumTask = [
                                    'rewardPoint' => $rewardRes['data']['point'],
                                    'orderCount' => $orderCount + 1
                                ];
                            }
                            break;
                        }
                    }

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
                                        'taskId' => $taskId,
                                        'rewardPoint' => $rewardPoint,
                                        'orderNumTask' => $orderNumTask, // 订单数任务奖励
                                        'settleDate' => !empty($orderData['settle_date']) ? date("Y-m-d", strtotime($orderData['settle_date'])) : '',
                                        'messageType' => MessageService::SYSTEM_ORDER_FANLI_APPLY_MESSAGE
                                    ]
                                ]
                            ]
                        )
                    );

                    // 记录到模板消息推送列表中
                    $jsonData = [
                        'orderSn' => $orderData['order_sn'],
                        'productName' => $orderData['product_name'],
                        'orderTime' => $orderData['order_time'],
                        'taskId' => $taskId,
                        'rewardPoint' => $orderData['return_fee'] * 10000,
                        'settleDate' => !empty($orderData['settle_date']) ? date("Y-m-d", strtotime($orderData['settle_date'])) : '',
                        'sourceName' => $orderData['source_id'] == 1 ? '京东' : '拼多多',
                    ];
                    $templateMsgService = new TemplateMsgService();
                    $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $uid, $orderData['order_sn'], TemplateMsgService::BUSINESS_ORDER_FANLI_APPLY_TMP_MSG, $jsonData);
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info(['actionName' => 'actionApply', 'message' => $e->getMessage()], 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 订单返利
     * 每5分钟执行一次
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/18 22:04
     */
    public function actionSettle()
    {
        try {
            $now = time();
            $orderList = Order::find()->where(['return_success' => 0])->groupBy("order_sn")->asArray()->all();
            if (!empty($orderList)) {
                foreach ($orderList as $key => $orderData) {
                    $uid = $orderData['uid'];
                    $orderSn = $orderData['order_sn'];
                    if (empty($uid)) {
                        continue;
                    }
                    // 已结算，执行返利
                    if (($orderData['source_id'] == 1 && $orderData['status'] == 17 && $orderData['settle_date'] <= date("Ymd")) || ($orderData['source_id'] == 2 && $orderData['status'] == 5)) {
                        // 更新订单数据
                        $updateRes = Order::updateAll(['return_success' => 1, 'updated_at' => $now], ['order_sn' => $orderSn]);
                        if (!$updateRes) {
                            Yii::info(['actionName' => 'actionSettle', 'message' => '更新订单表失败', 'orderData' => $orderData], 'trace');
                            continue;
                        }

                        // 发放积分奖励
                        $returnFee = Order::find()->where(['order_sn' => $orderSn])->sum('return_fee'); // 由于订单可以有多笔
                        $rewardPoint = $returnFee * 10000;
                        $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_ORDER_FANLI_AWARD_TYPE, $uid, $now);
                        $rewardRes = $rewardPointService->awardPoint($rewardPoint);
                        if (!$rewardRes['result']) {
                            Yii::info(['actionName' => 'actionSettle', 'message' => '发放积分奖励失败', 'orderData' => $orderData], 'trace');
                            continue;
                        }

                        // 记录到模板消息推送列表中
                        $jsonData = [
                            'orderSn' => $orderData['order_sn'],
                            'productName' => $orderData['product_name'],
                            'orderTime' => $orderData['order_time'],
                            'rewardPoint' => $rewardPoint,
                            'sourceName' => $orderData['source_id'] == 1 ? '京东' : '拼多多',
                        ];
                        $templateMsgService = new TemplateMsgService();
                        $templateMsgService->saveTemplateMsgRecord(MessageService::SYSTEM_USER, $uid, $orderData['order_sn'], TemplateMsgService::BUSINESS_ORDER_FANLI_SUCCESS_TMP_MSG, $jsonData);

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
                    }
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info(['actionName' => 'actionSettle', 'message' => $e->getMessage()], 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}