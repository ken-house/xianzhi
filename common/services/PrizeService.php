<?php
/**
 * 奖品服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/6 13:33
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\PrizeExchangeRecord;

use Yii;
use yii\helpers\ArrayHelper;

class PrizeService
{
    const UID_OFFSET = 100000; // 为了bitmap从0开始；

    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;
    private $redisKey;

    public function __construct()
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $this->redisKey = RedisHelper::RK('prizeExchangeNum', date("Ymd"));
    }

    /**
     * 获取单个奖品的已兑换个数
     *
     * @param $prizeId
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/4/6 13:38
     */
    public function getPrizeExchangedNum($prizeId)
    {
        $exchangedNum = $this->redisBaseCluster->zScore($this->redisKey, $prizeId);
        return intval($exchangedNum);
    }

    /**
     * 获取所有奖品的兑换个数情况
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 13:48
     */
    public function getAllPrizeExchangeNum()
    {
        return $this->redisBaseCluster->zRange($this->redisKey, 0, -1, true);
    }


    /**
     * 兑换奖品
     *
     * @param $prizeInfo
     * @param $userInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 14:47
     */
    public function exchange($prizeInfo, $userInfo)
    {
        $now = time();
        // 用户是否绑定微信
        if (empty($userInfo['wx'])) {
            return ToolsHelper::funcReturn("请绑定微信，以便发放奖品");
        }

        // 检测是否完成关联公众号任务
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);
        if(!$subscribe){
            return ToolsHelper::funcReturn("请先关联公众号，接收消息通知");
        }

        // 检测用户是否今日兑换过
        $redisKey = RedisHelper::RK('onceUserExchangePrize', $prizeInfo['id'], date("Ymd", $now));
        if ($this->redisBaseCluster->getBit($redisKey, $userInfo['uid'] - self::UID_OFFSET)) {
            return ToolsHelper::funcReturn("您今日已兑换过该奖品");
        }

        // 用户积分是否足够
        $userService = new UserService();
        $rewardPoint = $userService->getUserStructFromRedis($userInfo['uid'], 'reward_point');
        if ($rewardPoint < $prizeInfo['point']) {
            return ToolsHelper::funcReturn("积分不足，无法兑换");
        }

        // 检测是否兑换完成
        $exchangedNum = $this->getPrizeExchangedNum($prizeInfo['id']);
        if ($exchangedNum >= $prizeInfo['day_limit']) {
            return ToolsHelper::funcReturn("该奖品今日已全部兑换完，明天再来吧");
        }

        // 积分消耗
        $rewardPointService = new RewardPointService($prizeInfo['task_id'], $userInfo['uid'], $now);
        $pointRes = $rewardPointService->awardPoint($prizeInfo['point']);
        if ($pointRes['result']) { // 积分扣除成功
            $prizeExchangeRecord = new PrizeExchangeRecord();
            $prizeExchangeRecord->uid = $userInfo['uid'];
            $prizeExchangeRecord->prize_id = $prizeInfo['id'];
            $prizeExchangeRecord->point = $prizeInfo['point'];
            $prizeExchangeRecord->updated_at = $now;
            $prizeExchangeRecord->created_at = $now;
            if ($prizeExchangeRecord->save()) { // 写入记录表成功
                // 修改用户今日兑换
                $this->redisBaseCluster->setBit($redisKey, $userInfo['uid'] - self::UID_OFFSET, 1);
                // 增加奖品已兑换数
                $this->redisBaseCluster->zIncrBy($this->redisKey, 1, $prizeInfo['id']);

                return ToolsHelper::funcReturn("兑换成功，请联系客服领取", true);
            }
        }
        return ToolsHelper::funcReturn("兑换失败");
    }

    /**
     * 获取已兑换奖品的用户列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/1 17:37
     */
    public function getExchangeUserList()
    {
        $userArr = [];
        $uidArr = [];
        $noticeData = [];
        $dataList = PrizeExchangeRecord::find()->orderBy('id desc')->asArray()->all();
        if (!empty($dataList)) {
            foreach ($dataList as $key => $value) {
                $uidArr[] = $value['uid'];
            }

            // 批量查询数据库用户昵称
            $userService = new UserService();
            $userArr = $userService->getUserNickname($uidArr);
        }

        $prizeArr = ArrayHelper::index(Yii::$app->params['prizeArr'], 'id');

        if (!empty($userArr)) {
            foreach ($dataList as $key => $value) {
                $time = ToolsHelper::getTimeStrDiffNow($value['created_at']);
                $nickname = $userArr[$value['uid']];
                $prizeName = isset($prizeArr[$value['prize_id']]['title']) ? $prizeArr[$value['prize_id']]['title'] : '';
                if (!empty($prizeName)) {
                    $noticeData[] = $time . ' "' . $nickname . '"成功兑换了' . $prizeName;
                }
            }
        }
        return $noticeData;
    }


    /**
     * 获取用户vip兑换折扣
     *
     * @param $rewardPoint
     *
     * @return float|int
     *
     * @author     xudt
     * @date-time  2021/6/30 22:28
     */
    public function getUserVipDiscount($rewardPoint)
    {
        $currentLevel = ToolsHelper::getUserLevel($rewardPoint);
        $vipDiscountArr = Yii::$app->params['vipDiscountArr'];

        $vipDiscount = 1;
        // 会员优惠
        foreach ($vipDiscountArr as $level => $discount) {
            if ($currentLevel >= $level) {
                $vipDiscount = $discount / 100;
                break;
            }
        }

        return $vipDiscount;
    }
}