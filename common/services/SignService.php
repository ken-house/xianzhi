<?php
/**
 * 签到服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/6/2 14:21
 */

namespace common\services;

use Yii;

class SignService
{
    /**
     * 获取签到首页数据
     *
     * @param $continueSignDay
     * @param $lastSignDay
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/2 15:03
     */
    public function getSignInfo($continueSignDay, $lastSignDay)
    {
        $signPrizeArr = Yii::$app->params['signPrizeArr']; // 签到奖励规则
        $signTargetPrizeArr = Yii::$app->params['signTargetPrizeArr']; // 签到额外目标奖励规则

        // 今日签到奖励积分数
        $todayPoint = isset($signPrizeArr[$continueSignDay]) ? $signPrizeArr[$continueSignDay] : $signPrizeArr[7];

        // 明日签到奖励积分数
        $tomorrowPoint = isset($signPrizeArr[$continueSignDay + 1]) ? $signPrizeArr[$continueSignDay + 1] : $signPrizeArr[7];

        // 签到额外目标奖励
        $targetDay = $targetPoint = 0;
        foreach ($signTargetPrizeArr as $day => $point) {
            if ($continueSignDay < $day) {
                $targetDay = $day;
                $targetPoint = $point;
                break;
            }
        }
        return [
            'todayCanSign' => $lastSignDay == date("Ymd") ? 0 : 1,
            'continueDay' => $continueSignDay,
            'todayPoint' => $todayPoint,
            'tomorrowPoint' => $tomorrowPoint,
            'targetDay' => $targetDay,
            'targetPoint' => $targetPoint,
        ];
    }

    /**
     * 签到图形数据
     *
     * @param $uid
     * @param $signInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/8 22:08
     */
    public function getSignChartData($uid, $signInfo)
    {
        // 签到记录
        $recordList = RewardPointService::getRewardPointRecord($uid, RewardPointService::SIGN_AWARD_TYPE, 1, 5);
        $recordData = [];
        if (!empty($recordList)) {
            foreach ($recordList as $key => $value) {
                $recordData[date("n.j", strtotime($value['created_at']))] = 1;
            }
        }
        $signChartData = [];
        for ($i = -3; $i <= 3; $i++) {
            $day = date("n.j", strtotime($i . " days"));
            $isSign = isset($recordData[$day]) ? $recordData[$day] : 0;
            switch ($i) {
                case 1:
                    $point = $signInfo['tomorrowPoint'];
                    break;
                case 2:
                    $point = $signInfo['tomorrowPoint'] * 2;
                    break;
                case 3:
                    $point = $signInfo['tomorrowPoint'] * 4;
                    break;
                default:
                    $point = 0;
            }

            $signChartData[] = [
                'day' => $day == date("n.j") ? "今天" : $day,
                'is_sign' => $day == date("n.j") ? 1 : $isSign,
                'point' => $point >= Yii::$app->params['signPrizeArr'][7] ? Yii::$app->params['signPrizeArr'][7] : $point,
            ];
        }
        return $signChartData;
    }

}