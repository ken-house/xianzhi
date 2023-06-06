<?php
/**
 * 用户相关
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/23 15:15
 */

namespace console\controllers\cron;

use common\helpers\RedisHelper;
use common\models\mongo\MongodbRewardPointRecord;
use common\models\User;
use common\models\UserData;
use common\services\MessageService;
use common\services\RewardPointService;
use console\services\jobs\MessageJob;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class UserController extends Controller
{
    /**
     * 每日积分排行榜发放奖励
     * 脚本执行时间：每天早上8点执行一次
     *
     * @param int $date
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/5/23 15:21
     */
    public function actionRankReward($date = 0)
    {
        $now = time();
        $pointArr = [
            0 => 10000,
            1 => 5000,
            2 => 2000,
        ];
        $awardType = 103;

        if (empty($date)) {
            $date = date("Ymd", strtotime("-1 day"));
        }
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        // 用户积分排行榜
        $redisKey = RedisHelper::RK('userPointRank', $date);
        $dataList = $redisBaseCluster->zRevRange($redisKey, 0, 2);

        if (!empty($dataList)) {
            foreach ($dataList as $paiming => $uid) {
                $point = $pointArr[$paiming];

                // 发放奖励
                $rewardPointService = new RewardPointService($awardType, $uid, $now);
                $result = $rewardPointService->awardPoint($point);
                if ($result['result']) { // 排行榜奖励发送系统消息
                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => $uid
                                        ],
                                        'prizeInfo' => [
                                            'point' => $point
                                        ],
                                        'messageType' => MessageService::SYSTEM_POINT_AWARD_MESSAGE
                                    ]
                                ]
                            ]
                        )
                    );
                }
            }
        }
        return ExitCode::OK;
    }

    /**
     * 积分过期处理
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/11/4 09:59
     */
    public function actionPointDue()
    {
        $yearEnd = time();
        $yearStart = $yearEnd - 86400 * 365;
        $startUserId = 100000;
        // 查找注册时间为一年以前的用户的id
        $lastUserId = User::find()->select(['id'])->where(['<', 'created_at', $yearStart])->orderBy("id desc")->scalar();
        for ($uid = $startUserId; $uid <= $lastUserId; $uid++) {
            // 查询用户当前有效积分
            MongodbRewardPointRecord::resetTableName($uid);
            $effectPointSum = MongodbRewardPointRecord::find()->where(['uid' => intval($uid)])->andWhere(['>', 'point', 0])->andWhere(['>', 'created_at', $yearStart])->andWhere(['<=', 'created_at', $yearEnd])->sum('point');

            //读取用户当前账户积分
            $currentPoint = UserData::find()->select(['reward_point'])->where(['uid' => $uid])->scalar();

            $duePointNum = $currentPoint - $effectPointSum;
            if ($duePointNum > 0) { // 有积分过期
                $rewardPointService = new RewardPointService(RewardPointService::POINT_DUE_AWARD_TYPE, $uid, $yearEnd);
                $result = $rewardPointService->awardPoint(abs($duePointNum));
                if ($result['result']) { // 排行榜奖励发送系统消息
                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => $uid
                                        ],
                                        'prizeInfo' => [
                                            'point' => abs($duePointNum)
                                        ],
                                        'messageType' => MessageService::SYSTEM_POINT_DUE_MESSAGE
                                    ]
                                ]
                            ]
                        )
                    );
                }
            }
            sleep(1);
        }
        return ExitCode::OK;
    }
}