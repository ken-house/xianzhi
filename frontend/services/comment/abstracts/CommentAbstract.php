<?php
/**
 * 评论-抽象类
 * @author xudt
 * @date   : 2020/3/20 13:12
 */

namespace frontend\services\comment\abstracts;

abstract class CommentAbstract
{
    /**
     * 增加用户评论的文章  zset
     *
     * @param \rediscluster $redisCluster
     * @param string        $redisKey
     * @param int           $productId
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/20 17:12
     *
     */
    public function addUserCommentToRedis($redisCluster = null, $redisKey, $productId)
    {
        $now = time();
        $res = false;
        if ($redisCluster) {
            $res = $redisCluster->zAdd($redisKey, $now, $productId);
        }
        return $res;
    }

    /**
     * 修改商品评论数据
     *
     * @param \rediscluster $redisCluster
     * @param string        $redisKey
     * @param string        $field
     * @param int           $value
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/18 16:28
     *
     */
    public function incrRedisData($redisCluster = null, $redisKey = "", $field = "", $value = 1)
    {
        $res = false;
        if ($redisCluster) {
            $res = $redisCluster->hIncrBy($redisKey, $field, $value);
        }
        return $res;
    }
}