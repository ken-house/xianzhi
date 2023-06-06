<?php
/**
 * 战队竞赛
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/26 19:28
 */

namespace console\controllers\cron;

use common\models\TeamActivity;
use common\models\TeamPrize;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class TeamController extends Controller
{
    /**
     * 生成奖品列表
     * 执行时间为每天十点
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/8/26 19:46
     */
    public function actionCreatePrize()
    {
        $now = time();
        $currentTeamData = Yii::$app->params['teamData'];
        $teamActivityInfo = TeamActivity::find()->where(['period_num' => $currentTeamData['period_num']])->asArray()->one();
        if ($teamActivityInfo['end_at'] < $now && $teamActivityInfo['end_at'] + 12 * 3600 > $now) {
            $rewardPoint = $teamActivityInfo['reward_point'];

            // 一等奖
            $firstPrizeData = [
                'prize_name' => '一等奖',
                'reward_point' => intval($rewardPoint / 2),
                'period_num' => $currentTeamData['period_num'],
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $teamPrizeModel = new TeamPrize();
            $teamPrizeModel->attributes = $firstPrizeData;
            $teamPrizeModel->save();

            // 二等奖
            $secondPrizeData = [
                'prize_name' => '二等奖',
                'reward_point' => intval(($rewardPoint - $firstPrizeData['reward_point']) * 0.7),
                'period_num' => $currentTeamData['period_num'],
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $teamPrizeModel = new TeamPrize();
            $teamPrizeModel->attributes = $secondPrizeData;
            $teamPrizeModel->save();

            // 三等奖
            $thirdPrizeData = [
                'prize_name' => '三等奖',
                'reward_point' => intval($rewardPoint - $firstPrizeData['reward_point'] - $secondPrizeData['reward_point']),
                'period_num' => $currentTeamData['period_num'],
                'updated_at' => $now,
                'created_at' => $now,
            ];
            $teamPrizeModel = new TeamPrize();
            $teamPrizeModel->attributes = $thirdPrizeData;
            $teamPrizeModel->save();
        }

        return ExitCode::OK;
    }
}