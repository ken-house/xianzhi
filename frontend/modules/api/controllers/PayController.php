<?php
/**
 * 支付
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/3 11:54
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\GroupBuyService;
use common\services\WechatPayService;
use Yii;

class PayController extends BaseController
{
    /**
     * 下单支付 示例代码
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 11:56
     */
    /*public function actionOrder()
    {
        $params = [
            'price' => 1,
            'desc' => '测试',
            'order_no' => '1217752501201407033233368014',
            'openid' => 'ooKuK5N6NMy3RzFfQqsT0lzzCR9Q',
            'attach' => [],
        ];
        $wechatPayService = new WechatPayService();
        $data = $wechatPayService->orderPay($params);
        if (!empty($data)) {
            return ToolsHelper::funcReturn("下单成功", true, $data);
        }
        return ToolsHelper::funcReturn("下单失败");
    }*/

    /**
     * 支付成功回调通知
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/11/3 15:56
     */
    public function actionNotify()
    {
        $headers = Yii::$app->request->headers;
        $body = file_get_contents('php://input');

        $wechatPayService = new WechatPayService();
        $data = $wechatPayService->notify($headers, $body);
        if (!empty($data) && $data['trade_state'] == "SUCCESS") {
            // 返回数据格式，编写自己的业务逻辑
//            $data = [
//                'mchid' => '1603791498',
//                'appid' => 'wxe1ac8e07ccb42255',
//                'out_trade_no' => '1217752501201407033233368014',
//                'transaction_id' => '4200001176202111041127937334',
//                'trade_type' => 'JSAPI',
//                'trade_state' => 'SUCCESS',
//                'trade_state_desc' => '支付成功',
//                'bank_type' => 'OTHERS',
//                'attach' => '',
//                'success_time' => '2021-11-04T15:21:16+08:00',
//                'payer' => [
//                    'openid' => 'ooKuK5N6NMy3RzFfQqsT0lzzCR9Q',
//                ],
//                'amount' => [
//                    'total' => 1,
//                    'payer_total' => 1,
//                    'currency' => 'CNY',
//                    'payer_currency' => 'CNY',
//                ],
//            ];
            $attachData = json_decode($data['attach'], true);
            switch ($attachData['orderType']) {
                case 1: // 团购
                    $groupBuyService = new GroupBuyService();
                    $payRes = $groupBuyService->paySuccess($attachData['uid'], $data['out_trade_no']);
                    break;
                default:
                    $payRes = [
                        'result' => false
                    ];
            }

            if ($payRes['result']) {
                return ['code' => 'SUCCESS', 'message' => '成功'];
            }
            Yii::info(['func_name' => 'PayController.actionNotify', 'data' => $data, 'payRes' => $payRes], 'trace');
        }
        return ['code' => 'FAIL', 'message' => '失败'];
    }

    /**
     * 退款结果回调通知
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/11/3 15:56
     */
    public function actionRefundNotify()
    {
        $headers = Yii::$app->request->headers;
        $body = file_get_contents('php://input');

        $wechatPayService = new WechatPayService();
        $data = $wechatPayService->notify($headers, $body);
        if (!empty($data)) {
            // 编写自己的业务逻辑，注意做到幂等
            /*$data = [
                'mchid' => '1603791498',
                'out_trade_no' => '1217752501201407033233368014',
                'transaction_id' => '4200001176202111041127937334',
                'out_refund_no' => '4200001176202111041127937304',
                'refund_id' => '50301409972021110414051870729',
                'refund_status' => 'SUCCESS',
                'success_time' => '2021-11-04T15:33:46+08:00',
                'amount' => [
                    'total' => 1,
                    'refund' => 1,
                    'payer_total' => 1,
                    'payer_refund' => 1,
                ],
                'user_received_account' => '支付用户零钱',
            ];*/
            $groupBuyService = new GroupBuyService();
            if ($data['refund_status'] == 'SUCCESS') { // 退款成功
                $successTime = strtotime(str_replace("+08:00", '', str_replace("T", " ", $data['success_time'])));
                $refundRes = $groupBuyService->refundSuccess($data['out_trade_no'], $data['out_refund_no'], $successTime);
                if ($refundRes['result']) {
                    return ['code' => 'SUCCESS', 'message' => '成功'];
                }
                Yii::info(['func_name' => 'PayController.actionRefundNotify', 'message' => '退款成功', 'data' => $data], 'trace');
            } else { // 退款异常
                $refundRes = $groupBuyService->refundFail($data['out_trade_no']);
                Yii::info(['func_name' => 'PayController.actionRefundNotify', 'message' => '退款异常', 'data' => $data, 'refundRes' => $refundRes], 'trace');
                if ($refundRes['result']) {
                    return ['code' => 'SUCCESS', 'message' => '成功'];
                }
            }
        }
        return ['code' => 'FAIL', 'message' => '失败'];
    }


}