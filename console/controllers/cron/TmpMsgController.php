<?php
/**
 * 公众号模板消息
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/19 11:06
 */

namespace console\controllers\cron;

use common\models\PrizeExchangeRecord;
use common\models\UnionOpenid;
use common\models\User;
use common\services\OfficialAccountService;
use common\services\TemplateMsgService;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class TmpMsgController extends Controller
{
    /**
     * 买家我想要或买家回复，公众号推送消息通知给卖家
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/8/30 16:53
     */
    public function actionBuyerReply()
    {
        $endTime = time();
        $startTime = $endTime - 60; // 1分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getBuyerReplyMsgTmpList($startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = 'm6zVnpfeNWkVAiMOQeuRITiTHfVdW3tSreqBr7rB9Jo';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message/message',
                ];
                $remark = !empty($value['wx']) ? '可搜索微信号联系对方，物品卖出后请及时修改物品状态为已卖出，祝您生活愉快。' : '物品卖出后请及时修改物品状态为已卖出，祝您生活愉快。';
                $tmpData = [
                    'first' => [
                        'value' => '您的闲置物品有' . $value['count'] . '人想要，戳我进入小程序直接回复。',
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['product_name'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['price'] . '元',
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => $value['wx'],
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => $value['time'],
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => $remark,
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }


    /**
     * 卖家回复，未读消息推送给买家
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/8/30 16:53
     */
    public function actionSalerReply()
    {
        $endTime = time();
        $startTime = $endTime - 60; // 1分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getSalerReplyMsgTmpList($startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = 'm6zVnpfeNWkVAiMOQeuRITiTHfVdW3tSreqBr7rB9Jo';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message/message',
                ];
                $remark = !empty($value['wx']) ? '可搜索卖家微信号联系对方，祝您早日达成交易。' : '请尽快回复，祝您早日达成交易。';
                $tmpData = [
                    'first' => [
                        'value' => '有' . $value['count'] . '个卖家回复您，戳我进入小程序直接回复。',
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['product_name'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['price'] . '元',
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => $value['wx'],
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => $value['time'],
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => $remark,
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }

    /**
     * 审核不通过
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/1 14:30
     */
    public function actionAuditRefuse()
    {
        $endTime = time();
        $startTime = $endTime - 300; // 5分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getAuditMsgTmpList(TemplateMsgService::PRODUCT_AUDIT_REFUSE_TMP_MSG, $startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = '0HgHNwLSUfmzWHMca08x7eo8IaS13LR6wGuYwGG7qZw';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message_system/message_system',
                ];
                $tmpData = [
                    'first' => [
                        'value' => '您有' . $value['count'] . '个物品审核不通过，戳我进入小程序修改。',
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['product_name'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['price'] . '元',
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => $value['audit_reason'],
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => $value['time'],
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => "请根据审核原因进行修改，修改后重新提交发布，我们将尽快为您审核。",
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }

    /**
     * 审核通过
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/1 14:30
     */
    public function actionAuditPass()
    {
        $endTime = time();
        $startTime = $endTime - 300; // 5分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getAuditMsgTmpList(TemplateMsgService::PRODUCT_AUDIT_PASS_TMP_MSG, $startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = 'jo1FJWGaiCf-ADCtrOkXDo0e_1em0hV6h6drJDza45A';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message_system/message_system',
                ];
                $tmpData = [
                    'first' => [
                        'value' => '您有' . $value['count'] . '个物品审核通过，戳我进小程序查看。',
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['product_name'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['price'] . '元',
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => "分享闲置物品到微信群或朋友圈，可快速完成交易。",
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }

    /**
     * 审核强制下架
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/1 14:30
     */
    public function actionAuditDown()
    {
        $endTime = time();
        $startTime = $endTime - 300; // 5分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getAuditMsgTmpList(TemplateMsgService::PRODUCT_AUDIT_DOWN_TMP_MSG, $startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = '013c349xnEQJfrIJhS_VAPu4a39xD5pLeBmgKpIkQ-o';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message_system/message_system',
                ];
                $tmpData = [
                    'first' => [
                        'value' => '您的商品可能存在违规信息，已被强制下架。',
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['product_name'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['price'] . '元',
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => $value['audit_reason'],
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => $value['time'],
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => "请根据违规原因进行修改，如有疑问，可联系客服。",
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }


    /**
     * 订单返利提醒通知
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/1 14:30
     */
    public function actionOrderFanli()
    {
        $endTime = time();
        $startTime = $endTime - 300; // 5分钟
        $templateMsgService = new TemplateMsgService();
        $dataList = $templateMsgService->getOrderFanliMsgTmpList($startTime, $endTime);
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                // 检查是否关注公众号
                $userInfo = $officialAccountService->getUserInfo($value['official_openid']);
                if (empty($userInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = 'ftYcp8luedQ9iuGLUH_nv7CwE-74pCoQVuDgbtanLYg';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/business_product_page/order/order',
                ];

                if ($value['type'] == TemplateMsgService::BUSINESS_ORDER_FANLI_SUCCESS_TMP_MSG) {
                    $first = "您的订单已返利成功，返利以积分放入账户，点击请查看详情";
                    $statusName = "已返利";
                } else {
                    $first = "您的订单申请返利成功，返利以积分放入账户，具体到账时间请关注订单列表";
                    $statusName = "申请通过";
                }

                $tmpData = [
                    'first' => [
                        'value' => $first,
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => $value['orderSn'],
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $value['sourceName'],
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => date("Y-m-d H:i:s", $value['orderTime']),
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => $value['rewardPoint'] . "积分",
                        'color' => '#173177',
                    ],
                    'keyword5' => [
                        'value' => $statusName,
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => "小程序选购商品价格更低，闲置购物有返利。",
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($value['official_openid'], $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }

    /**
     * 提现结果消息通知
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/28 11:02
     */
    public function actionWithdraw()
    {
        $endTime = time();
        $startTime = $endTime - 300; // 5分钟
        $dataList = PrizeExchangeRecord::find()->where(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->andWhere(['<>', 'status', 0])->asArray()->all();
        if (!empty($dataList)) {
            $officialAccountService = new OfficialAccountService();
            foreach ($dataList as $key => $value) {
                $userInfo = User::find()->where(['id'=>$value['uid']])->asArray()->one();
                $officialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid'=>$userInfo['wx_openid']])->scalar();

                // 检查是否关注公众号
                $officialAccountInfo = $officialAccountService->getUserInfo($officialOpenid);
                if (empty($officialAccountInfo['subscribe'])) {
                    continue;
                }

                // 发送模板消息
                $templateId = 'KNBpYN7dxjzMdPZkap18qHiEd3eO3Foz-tSXVPc2Vec';
                $minprogram = [
                    'appid' => Yii::$app->params['weChat']['appid'],
                    'pagepath' => '/pages/message_system/message_system',
                ];

                if ($value['status']==1) {
                    $first = "兑换奖品审核通过，请联系客服领取奖励，7天内有效，逾期将无法领取。";
                }else{
                    $first = "兑换奖品审核不通过，审核原因：".$value['audit_reason']."。如有疑问，请联系客服。";
                }

                $prizeName = "";
                $prizeArr = Yii::$app->params['prizeArr'];
                foreach ($prizeArr as $k=>$v){
                    if($v['id']==$value['prize_id']){
                        $prizeName = $v['title'];
                        break;
                    }
                }

                $tmpData = [
                    'first' => [
                        'value' => $first,
                        'color' => '#173177',
                    ],
                    'keyword1' => [
                        'value' => "小区闲置物品信息交流平台",
                        'color' => '#173177',
                    ],
                    'keyword2' => [
                        'value' => $prizeName,
                        'color' => '#173177',
                    ],
                    'keyword3' => [
                        'value' => "微信号：".$userInfo['wx'],
                        'color' => '#173177',
                    ],
                    'keyword4' => [
                        'value' => date("Y-m-d H:i:s", $value['audit_at']),
                        'color' => '#173177',
                    ],
                    'remark' => [
                        'value' => "现金提现以微信红包的方式发放，请至微信领取。其他奖品请联系客服提供相应信息。",
                        'color' => '#173177',
                    ],
                ];
                $sendRes = $officialAccountService->sendTmpMsg($officialOpenid, $templateId, "", $minprogram, $tmpData);
                $value['result'] = $sendRes ? 1 : 0;
                Yii::info($value, "tmpMsg");
                sleep(2);
            }
        }
        return ExitCode::OK;
    }

}