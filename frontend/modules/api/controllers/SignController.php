<?php
/**
 * 签到
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/6/2 13:52
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\RewardPointService;
use common\services\SignService;
use common\services\UserService;
use Yii;

class SignController extends BaseController
{
    /**
     * 用户签到
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/2 14:46
     */
    public function actionSign()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();
        $today = date("Ymd", $now);
        $lastSignDay = $userInfo['last_sign_day']; // 最后一次签到日期
        $currentContinueSignDay = $userInfo['continue_sign_day']; // 当前连续签到天数

        if (date("Ymd") == $lastSignDay) {
            return ToolsHelper::funcReturn("今日已签到");
        }

        $continueSignDay = 1;
        if ($lastSignDay == date("Ymd", strtotime("-1 day"))) {
            $continueSignDay = $currentContinueSignDay + 1;
        }

        $signPrizeArr = Yii::$app->params['signPrizeArr']; // 签到奖励规则
        $signTargetPrizeArr = Yii::$app->params['signTargetPrizeArr']; // 签到额外目标奖励规则
        $rewardPoint = isset($signPrizeArr[$continueSignDay]) ? $signPrizeArr[$continueSignDay] : $signPrizeArr[7]; // 今日可获得签到积分

        // 更改用户签到数据
        $userService = new UserService();
        $res = $userService->saveUserData(
            $uid,
            [
                'last_sign_day' => $today,
                'continue_sign_day' => $continueSignDay,
                'updated_at' => $now
            ]
        );
        if ($res) {
            // 发放签到奖励
            $rewardPointService = new RewardPointService(RewardPointService::SIGN_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->awardPoint($rewardPoint);
            $targetAwardTips = '';
            if ($rewardRes['result']) {
                // 签到目标奖励
                if (isset($signTargetPrizeArr[$continueSignDay])) {
                    $rewardPointService = new RewardPointService(RewardPointService::SING_TARGET_AWARD_TYPE, $uid, $now);
                    $rewardRes = $rewardPointService->awardPoint($signTargetPrizeArr[$continueSignDay]);
                    if ($rewardRes['result']) {
                        $targetAwardTips = "，您已连续签到" . ($continueSignDay) . "天，额外奖励" . $signTargetPrizeArr[$continueSignDay] . "积分已放入账户";
                    }
                }

                // 获取签到成功后的值
                $signService = new SignService();
                $signInfo = $signService->getSignInfo($continueSignDay, $today);

                return ToolsHelper::funcReturn(
                    "签到成功",
                    true,
                    [
                        'rewardTips' => "签到成功，" . $rewardPoint . "积分已放入账户" . $targetAwardTips,
                        'signInfo' => $signInfo
                    ]
                );
            }
            return ToolsHelper::funcReturn("签到成功，奖励发放失败");
        }
        return ToolsHelper::funcReturn("签到失败");
    }
}