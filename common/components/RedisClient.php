<?php
namespace common\components;
use yii\base\Component;

/**
 * Class RedisClient
 * @package common\components
 */
class RedisClient extends Component
{
    /**
     * @var 主机配置
     */
    public $seeds;

    /**
     * @var 连接超时限制，单位秒，超时返回false
     */
    public $timeout;

    /**
     * @var 读取某个key超时限制，单位秒，超时返回false
     */
    public $readTimeout;

    /**
     * @var 使用长连接
     */
    public $persistent;

    /**
     * @var \RedisCluster
     */
    protected $redisCluster;

    /**
     * @author   xudt
     * @dateTime 2019/11/2 21:53
     */
    public function init()
    {
        $this->redisCluster = new \RedisCluster(null, $this->seeds, $this->timeout, $this->readTimeout, $this->persistent);
    }

    /**
     * @author   xudt
     * @dateTime 2019/11/2 21:53
     * @return \RedisCluster
     */
    public function getRedisCluster()
    {
        return $this->redisCluster;
    }

}