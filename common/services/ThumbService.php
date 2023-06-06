<?php
/**
 * 点赞服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/3/9 11:51
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;

use console\services\jobs\UserEffectJob;
use Yii;

class ThumbService
{
    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;
    private $redisKey;
    private $productId;
    private $uid;

    public function __construct($productId, $uid)
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $this->redisKey = RedisHelper::RK('userProductData', 'thumb', $uid);
        $this->uid = $uid;
        $this->productId = $productId;
    }

    /**
     * 用户是否对商品进行点赞
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/3/9 16:20
     */
    public function isThumbProductOver()
    {
        $addTime = $this->redisBaseCluster->zScore($this->redisKey, $this->productId);
        if ($addTime > 0) {
            return 1;
        }
        return 0;
    }

    /**
     * 点赞
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 11:12
     */
    public function thumbProduct()
    {
        $now = time();
        if ($this->redisBaseCluster->zAdd($this->redisKey, $now, $this->productId)) {
            // 增加商品点赞数
            $redisKey = RedisHelper::RK('productData', $this->productId);
            $this->redisBaseCluster->hIncrBy($redisKey, 'thumb_num', 1);

            return ToolsHelper::funcReturn("点赞成功", true);
        }
        return ToolsHelper::funcReturn("点赞失败");
    }

    /**
     * 取消点赞
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 11:14
     */
    public function cancelThumbProduct()
    {
        if ($this->redisBaseCluster->zRem($this->redisKey, $this->productId)) {
            $redisKey = RedisHelper::RK('productData', $this->productId);
            $this->redisBaseCluster->hIncrBy($redisKey, 'thumb_num', -1);
            return ToolsHelper::funcReturn("取消点赞成功", true);
        }
        return ToolsHelper::funcReturn("取消点赞失败");
    }
}