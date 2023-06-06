<?php
/**
 * 周期性脚本
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/12 10:45
 */

namespace console\controllers\cron;

use common\helpers\RedisHelper;
use common\models\mongo\MongodbMessageDetailRecord;
use common\models\mongo\MongodbMessageRecord;
use common\models\mongo\MongodbProductCommentRecord;
use common\models\mongo\MongodbProductReplyRecord;
use common\models\mongo\MongodbRewardPointRecord;
use yii\console\Controller;

use Yii;
use yii\console\ExitCode;

class CronManageController extends Controller
{
    /**
     * Redis管理
     * 包括过期时间设置
     * 脚本执行时间：每天凌晨2点执行一次
     *
     * @param string $date 20210412
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/4/12 11:46
     */
    public function actionRedis($date = '')
    {
        $expire3Day = 86400 * 3; // 过期时间
        if (empty($date)) {
            $date = date("Ymd", strtotime('-1 day')); // 昨日日期
        }
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();

        // 商品点赞、评论、浏览、想要数据
        $awardTypeArr = Yii::$app->params['awardType'];
        foreach ($awardTypeArr as $typeId => $value) {
            $redisKey = RedisHelper::RK('onceOfDateRewardPoint', $typeId, $date);
            if ($redisBaseCluster->exists($redisKey)) {
                $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
            }
        }

        // 商品擦亮限制每天一次
        $redisKey = RedisHelper::RK('onceOfDateRefresh', $date);
        if ($redisBaseCluster->exists($redisKey)) {
            $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
        }

        // 用户积分排行榜
        $redisKey = RedisHelper::RK('userPointRank', $date);
        if ($redisBaseCluster->exists($redisKey)) {
            $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
        }

        // 奖品每日兑奖个数
        $redisKey = RedisHelper::RK('prizeExchangeNum', $date);
        if ($redisBaseCluster->exists($redisKey)) {
            $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
        }

        // 用户是否兑换过商品
        $prizeArr = Yii::$app->params['prizeArr'];
        foreach ($prizeArr as $key => $value) {
            $redisKey = RedisHelper::RK('onceUserExchangePrize', $value['id'], $date);
            if ($redisBaseCluster->exists($redisKey)) {
                $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
            }
        }

        // 大转盘记录用户今日使用次数
        $redisKey = RedisHelper::RK('wheelUserCount', $date);
        if ($redisBaseCluster->exists($redisKey)) {
            $redisBaseCluster->expire($redisKey, $expire3Day + rand(0, 180));
        }



        return ExitCode::OK;
    }

    /**
     * Mongo管理
     * 包括创建索引
     *
     *
     * @author     xudt
     * @date-time  2021/4/12 11:46
     */
    public function actionMongo()
    {
        $mongodbMessageRecord = new MongodbMessageRecord();
        $collection = $mongodbMessageRecord->getCollection();

        // 消息日志表
        $collection->createIndex(['delete_uid' => -1, 'updated_at' => -1], ['background' => true, 'name' => 'ck_du']);

        // 消息详情表
        for ($i = 0; $i < 256; $i++) {
            MongodbMessageDetailRecord::resetTableNameByNum($i);
            $collection = MongodbMessageDetailRecord::getCollection();
            $collection->createIndex(['sender_uid' => 1, 'product_id' => 1], ['background' => true, 'name' => 'ck_sp']);
            $collection->createIndex(['getter_uid' => 1], ['background' => true, 'name' => 'ck_guid']);
            sleep(1);
        }
        sleep(10);

        // 评论表
        for ($i = 0; $i < 100; $i++) {
            MongodbProductCommentRecord::resetTableNameByNum($i);
            $collection = MongodbProductCommentRecord::getCollection();
            $collection->createIndex(['comment_id' => 1], ['background' => true, 'name' => 'ck_cid']);
            $collection->createIndex(['product_id' => 1, 'created_at' => -1], ['background' => true, 'name' => 'ck_pc']);
            sleep(1);
        }
        sleep(10);

        // 回复表
        for ($i = 0; $i < 256; $i++) {
            MongodbProductReplyRecord::resetTableNameByNum($i);
            $collection = MongodbProductReplyRecord::getCollection();
            $collection->createIndex(['reply_id' => 1], ['background' => true, 'name' => 'ck_rid']);
            $collection->createIndex(['comment_id' => 1, 'created_at' => -1], ['background' => true, 'name' => 'ck_cc']);
            sleep(1);
        }
        sleep(10);

        // 评论表
        for ($i = 0; $i < 100; $i++) {
            MongodbRewardPointRecord::resetTableNameByNum($i);
            $collection = MongodbRewardPointRecord::getCollection();
            $collection->createIndex(['uid' => 1, 'created_at' => -1], ['background' => true, 'name' => 'ck_uc']);
            sleep(1);
        }

        return ExitCode::OK;
    }
}