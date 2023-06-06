<?php
/**
 * 大转盘
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/27 14:36
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\RewardPointService;
use common\services\WheelService;

use Yii;
use yii\helpers\ArrayHelper;

class WheelController extends BaseController
{
    const MAXCOUNT = 5;

    /**
     * 大转盘首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/27 15:41
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        // 奖品列表
        $wheelPrize = Yii::$app->params['wheelPrize'];
        $prizeList = ArrayHelper::getColumn($wheelPrize, 'prize_name');
        $colorList = ArrayHelper::getColumn($wheelPrize, 'color');

        // 剩余次数
        $wheelService = new WheelService($uid);
        $count = $wheelService->getUserCount();

        $leaveCount = self::MAXCOUNT - $count;

        // 是否能转盘
        $canIRun = 0;
        if ($wheelService->getWheelUserRun() || $count == 0) { // 如果为第一次或看完视频奖励一次机会
            $canIRun = 1;
        }

        return ToolsHelper::funcReturn(
            "大转盘首页",
            true,
            [
                'leaveCount' => $leaveCount < 0 ? 0 : $leaveCount,
                'canIRun' => $canIRun,
                'prizeList' => $prizeList,
                'colorList' => $colorList
            ]
        );
    }

    /**
     * 转盘抽奖，增加已抽奖次数，发放奖励
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/27 16:58
     */
    public function actionRun()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();

        $wheelService = new WheelService($uid);
        $count = $wheelService->getUserCount();
        if (!($wheelService->getWheelUserRun() || $count == 0)) { // 如果为第一次或看完视频奖励一次机会
            return ToolsHelper::funcReturn("未完成观看激励视频");
        }
        if ($count > self::MAXCOUNT) {
            return ToolsHelper::funcReturn("今天的机会都用完了");
        }

        // 转盘奖品
        $wheelPrize = Yii::$app->params['wheelPrize'];
        $choosePrizeInfo = [];
        $randNum = rand(1, 9999);
        foreach ($wheelPrize as $key => $value) {
            if ($randNum >= $value['percent']['min'] && $randNum <= $value['percent']['max']) {
                $choosePrizeInfo = $value;
                break;
            }
        }

        // 已转盘次数增加+1
        $count = $wheelService->incrUserCount();
        $leaveCount = self::MAXCOUNT - $count;
        // 是否能转盘置为0
        $wheelService->setWheelUserRun(0);

        // 发放奖励
        $rewardPointService = new RewardPointService(RewardPointService::WHEEL_AWARD_TYPE, $uid, $now);
        $rewardPointService->awardPoint($choosePrizeInfo['point']);

        return ToolsHelper::funcReturn(
            "中奖啦",
            true,
            [
                'choosePrizeInfo' => $choosePrizeInfo,
                'canIRun' => 0,
                'leaveCount' => $leaveCount < 0 ? 0 : $leaveCount,
            ]
        );
    }


    /**
     * 看完视频后奖励
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/3 14:30
     */
    public function actionReward()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $wheelService = new WheelService($uid);
        $count = $wheelService->getUserCount();
        if ($count < self::MAXCOUNT && !$wheelService->setWheelUserRun(1)) {
            return ToolsHelper::funcReturn("看完视频奖励", true, ['canIRun' => 1]);
        }
        return ToolsHelper::funcReturn("数据异常,请稍候重试");
    }
}