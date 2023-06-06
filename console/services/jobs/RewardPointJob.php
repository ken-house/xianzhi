<?php
/**
 * 消息队列消费者
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/3/15 11:52
 */

namespace console\services\jobs;

use common\helpers\RedisHelper;
use common\models\mongo\MongodbRewardPointRecord;
use common\models\UserData;
use common\services\RewardPointService;
use yii\console\ExitCode;

use Yii;

class RewardPointJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $data;

    /**
     * 积分消费
     *
     * @param \yii\queue\Queue $queue
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/12/18 13:14
     *
     */
    public function execute($queue)
    {
        try {
            $currentPoint = $this->data['current_point'];
            $uid = $this->data['uid'];
            // 更新用户数据表中的reward_point字段
            $res = UserData::updateAll(['reward_point' => $currentPoint, 'updated_at' => time()], ['uid' => $uid]);
            if ($res) {
                // 写入积分记录
                MongodbRewardPointRecord::resetTableName($uid);
                $rewardPointRecord = new MongodbRewardPointRecord();
                $rewardPointRecord->uid = intval($uid);
                $rewardPointRecord->type = intval($this->data['type']);
                $rewardPointRecord->title = $this->data['title'];
                $rewardPointRecord->point = intval($this->data['point']);
                $rewardPointRecord->current_point = intval($currentPoint);
                $rewardPointRecord->created_at = intval($this->data['created_at']);
                if ($rewardPointRecord->save()) {
                    // 为实现跑马灯效果
                    /** @var \redisCluster $redisBaseCluster */
                    $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
                    $redisKey = RedisHelper::RK('userAwardPointRecord');
                    $redisBaseCluster->zAdd($redisKey, $this->data['created_at'], json_encode($this->data));
                    if ($redisBaseCluster->zCard($redisKey) >= 100) {
                        $redisBaseCluster->zRemRangeByRank($redisKey, -1, -100);
                    }

                    // 积分排行榜数据（不包含排行榜奖励和扣除积分类型）
                    if ($rewardPointRecord->type != RewardPointService::RANK_AWARD_TYPE && $rewardPointRecord->point > 0) {
                        if (!in_array($uid, [100001])) {
                            $rankRedisKey = RedisHelper::RK('userPointRank', date("Ymd", $rewardPointRecord->created_at));
                            $redisBaseCluster->zIncrBy($rankRedisKey, $rewardPointRecord->point, $uid);
                        }
                    }

                    return ExitCode::OK;
                }
                // 记录日志
                Yii::info(
                    [
                        'data' => $this->data,
                        'error' => '写入积分记录失败',
                    ],
                    'rewardPointConsumer'
                );
            }

            Yii::info(
                [
                    'data' => $this->data,
                    'error' => '更新user_data表失败',
                ],
                'rewardPointConsumer'
            );
            return ExitCode::UNSPECIFIED_ERROR;
        } catch (\Exception $e) {
            Yii::info(
                [
                    'data' => $this->data,
                    'error' => $e->getMessage()
                ],
                'rewardPointConsumer'
            );
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}