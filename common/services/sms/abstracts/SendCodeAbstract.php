<?php
/**
 * @author xudt
 * @date   : 2019/11/2 16:39
 */

namespace common\services\sms\abstracts;

use common\helpers\ToolsHelper;
use Yii;

abstract class SendCodeAbstract
{
    CONST CODETIMEOUT = 300;  //短信有效时间为5分钟

    abstract function send($target,$type);

    /** @var \redisCluster $redisCluster */
    private $redisCluster;

    public function __construct()
    {
        $this->redisCluster = Yii::$app->get('redisBase')->getRedisCluster();
    }

    /**
     * 保存验证码到redis
     *
     * @author   xudt
     * @dateTime 2019/11/7 18:04
     *
     * @param $redisKey
     * @param $code
     *
     * @return array
     */
    protected function saveCodeToRedis($redisKey, $code)
    {
        $data = [
            'verify_code' => $code,
            'try_count' => 0,
        ];

        $res = $this->redisCluster->hMSet($redisKey, $data);
        if ($res) {
            $this->redisCluster->expire($redisKey, self::CODETIMEOUT);
            return ToolsHelper::funcReturn("保存成功", true);
        } else {
            return ToolsHelper::funcReturn("保存数据失败");
        }
    }

    /**
     * 从redis获取验证码
     *
     * @author   xudt
     * @dateTime 2019/11/7 18:49
     *
     * @param $redisKey
     *
     * @return array
     */
    protected function getCodeFromRedis($redisKey)
    {
        $data = $this->redisCluster->hGetAll($redisKey);
        if (!empty($data)) {
            return ToolsHelper::funcReturn("读取成功", true, $data);
        } else {
            return ToolsHelper::funcReturn("短信验证码已失效");
        }
    }

    /**
     * 尝试次数自增1
     * @author   xudt
     * @dateTime 2019/11/8 11:08
     * @param $redisKey
     */
    protected function incrTryCountToRedis($redisKey)
    {
        if($this->redisCluster->hExists($redisKey,'try_count')){
            $this->redisCluster->hIncrBy($redisKey, 'try_count', 1);
        }
    }

    /**
     * 删除短信验证码key
     * @author   xudt
     * @dateTime 2020/2/26 16:25
     * @param $redisKey
     */
    public function delPhoneCodeRedis($redisKey)
    {
        $this->redisCluster->del($redisKey);
    }

}