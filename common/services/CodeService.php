<?php
/**
 * 验证码服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/18 19:26
 */

namespace common\services;

use common\helpers\RedisHelper;
use Yii;

class CodeService
{
    const MIN_PROGRAM_CODE = 1; // 绑定小程序验证码
    const EXPIRE_300_SECOND = 300; // 5分钟

    /**
     * 保存绑定小程序验证码到redis
     *
     * @param $openId
     * @param $code
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/8/18 19:35
     */
    public function saveMinProgramCode($openId, $code)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        // 写入到redis并设置过期时间为5分钟
        $redisKey = RedisHelper::RK('smsCode', self::MIN_PROGRAM_CODE, $openId);
        return $redisBaseCluster->set($redisKey, $code, self::EXPIRE_300_SECOND);
    }

}