<?php
/**
 * 大转盘服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/27 15:20
 */

namespace common\services;

use common\helpers\RedisHelper;
use Yii;

class WheelService
{
    const UID_OFFSET = 100000; // 为了bitmap从0开始；

    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;
    private $redisKey;
    private $uid;

    public function __construct($uid)
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $this->redisKey = RedisHelper::RK('wheelUserCount', date("Ymd"));
        $this->uid = $uid;
    }

    /**
     * 获取用户今日已转盘次数
     *
     * @return float
     *
     * @author     xudt
     * @date-time  2021/4/27 15:36
     */
    public function getUserCount()
    {
        $count = $this->redisBaseCluster->zScore($this->redisKey, $this->uid);
        return intval($count);
    }

    /**
     * 自增用户今日已转盘次数
     *
     * @return float
     *
     * @author     xudt
     * @date-time  2021/4/27 15:38
     */
    public function incrUserCount()
    {
        return $this->redisBaseCluster->zIncrBy($this->redisKey, 1, $this->uid);
    }

    /**
     * 判断用户是否可以转转盘
     *
     *
     * @author     xudt
     * @date-time  2021/5/3 14:02
     */
    public function getWheelUserRun()
    {
        $redisKey = RedisHelper::RK('wheelUserRun');
        return $this->redisBaseCluster->getBit($redisKey, $this->uid - self::UID_OFFSET);
    }

    /**
     * 设置用户是否可以转转盘
     *
     * @param int $value
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/5/3 14:05
     */
    public function setWheelUserRun($value = 0)
    {
        $redisKey = RedisHelper::RK('wheelUserRun');
        return $this->redisBaseCluster->setBit($redisKey, $this->uid - self::UID_OFFSET, $value);
    }
}