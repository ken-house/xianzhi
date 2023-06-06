<?php
/**
 * 任务相关
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/24 21:17
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\FanliService;
use common\services\RewardPointService;
use common\services\SignService;
use Yii;
use yii\helpers\ArrayHelper;

class TaskController extends BaseController
{
    /**
     * 做任务赚积分
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/3 13:56
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $lastSignDay = isset($userInfo['last_sign_day']) ? $userInfo['last_sign_day'] : 0; // 最后一次签到日期
        $continueSignDay = isset($userInfo['continue_sign_day']) ? $userInfo['continue_sign_day'] : 1; // 连续签到天数

        $now = time();

        // 会员等级
        $userLevelArr = Yii::$app->params['userLevelArr'];
        $userLevelPointArr = array_flip($userLevelArr);

        $currentLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
        $nextLevel = $currentLevel + 1;
        $nextLevelDiffPoint = $userInfo['reward_point'] - $userLevelPointArr[$currentLevel]; // 用户积分超过当前等级的积分
        $levelDiffPoint = $userLevelPointArr[$nextLevel] - $userLevelPointArr[$currentLevel]; //当前等级距下一个等级的积分
        $percent = round($nextLevelDiffPoint / $levelDiffPoint, 2) * 100;

        // 会员特权
        $vipPrivillegeArr = $this->getVipPrivillegeArr($currentLevel);

        // 完成任务赚金币
        $rewardPointService = new RewardPointService(0, $uid, $now);
        $taskList = $rewardPointService->getTaskList();

        // 签到数据详情
        $signService = new SignService();
        $signInfo = $signService->getSignInfo($continueSignDay, $lastSignDay);

        // 签到图形数据
        $signChartData = $signService->getSignChartData($uid, $signInfo);

        // 订单数奖励情况
        $orderNumAwardArr = ToolsHelper::getOrderNumAward($uid);

        return ToolsHelper::funcReturn(
            "做任务赚积分",
            true,
            [
                'userInfo' => $userInfo,
                'taskList' => $taskList,
                'signInfo' => $signInfo,
                'signChartData' => $signChartData,
                "currentLevel" => $currentLevel,
                "nextLevel" => $nextLevel,
                "percent" => $percent,
                "nextLevelPoint" => $userLevelPointArr[$nextLevel] - $userInfo['reward_point'],
                "vipPrivillegeArr" => array_values($vipPrivillegeArr),
                "orderNumAwardArr" => $orderNumAwardArr,
            ]
        );
    }

    /**
     * 会员特权
     *
     * @param $currentLevel
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/6/30 18:18
     */
    private function getVipPrivillegeArr($currentLevel)
    {
        // 会员特权列表
        $vipPrivillegeArr = Yii::$app->params['vipPrivillegeArr'];
        $vipDiscountArr = Yii::$app->params['vipDiscountArr'];
        foreach ($vipPrivillegeArr as $key => &$value) {
            if ($value['style'] == "no-ad") {
                unset($vipPrivillegeArr[$key]);
                continue;
            }
            $value['status'] = 0;
            if ($currentLevel >= $value['min_level']) {
                $value['status'] = 1;
            }
        }

        // 会员优惠
        foreach ($vipDiscountArr as $level => $discount) {
            if ($currentLevel >= $level) {
                $vipPrivillegeArr[4]['info'] = str_replace("%s", $discount / 10, $vipPrivillegeArr[4]['info']);
                break;
            }
        }
        $vipPrivillegeArr[4]['info'] = str_replace("%s", 9.8, $vipPrivillegeArr[4]['info']);

        ArrayHelper::multisort($vipPrivillegeArr, ['status', 'sort'], [SORT_DESC, SORT_ASC]);
        return array_values($vipPrivillegeArr);
    }

    /**
     * 日常任务完成后发放奖励
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 21:25
     */
    public function actionDailyTaskFinish()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $typeId = Yii::$app->request->post("type");
        if (empty($typeId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $now = time();

        $rewardPointService = new RewardPointService($typeId, $uid, $now);
        return $rewardPointService->onceTaskAwardPoint();
    }

    /**
     * 阶段任务发放奖励
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 21:25
     */
    public function actionTargetTaskFinsih()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $typeId = Yii::$app->request->post("type");
        if (empty($typeId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $now = time();

        switch ($typeId) {
            case RewardPointService::SING_DOUBLE_AWARD_TYPE:
                $continueSignDay = $userInfo['continue_sign_day'];
                $signPrizeArr = Yii::$app->params['signPrizeArr']; // 签到奖励规则
                $rewardPoint = isset($signPrizeArr[$continueSignDay]) ? $signPrizeArr[$continueSignDay] : $signPrizeArr[7]; // 今日可获得签到积分
                break;
            case RewardPointService::BUSINESS_ORDER_NUM_AWARD_TYPE:
                $fanliService = new FanliService();
                $statisticData = $fanliService->getStatisticData($uid);
                $orderNumRewardArr = Yii::$app->params['orderNumRewardArr']; // 订单数奖励
                if (!empty($orderNumRewardArr) && $statisticData['order_count'] > 0) {
                    foreach ($orderNumRewardArr as $num => $point) {
                        if ($num == $statisticData['order_count']) { // 发放奖励
                            $rewardPoint = $point;
                            break;
                        }
                    }
                }
                break;
            default:
                $rewardPoint = 0;
        }

        if (empty($rewardPoint)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $rewardPointService = new RewardPointService($typeId, $uid, $now);
        if ($typeId == RewardPointService::BUSINESS_ORDER_NUM_AWARD_TYPE) {
            $rewardRes = $rewardPointService->awardPoint($rewardPoint);
            if ($rewardRes['result']) {
                return ToolsHelper::funcReturn("完成订单数奖励，" . $rewardRes['data']['point'] . "积分已放入账户", true);
            }
            return ToolsHelper::funcReturn("任务已完成");
        } else {
            return $rewardPointService->onceTaskAwardPoint($rewardPoint);
        }
    }

}