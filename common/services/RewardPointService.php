<?php
/**
 * 积分服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/2 11:44
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\mongo\MongodbRewardPointRecord;
use common\models\UserData;
use console\services\jobs\RewardPointJob;
use Yii;
use yii\helpers\ArrayHelper;

class RewardPointService
{
    // 任务类型
    const NORMAL_TASK_TYPE = 0;
    const ONCE_TASK_TYPE = 1;
    const DAILY_TASK_TYPE = 2;


    // 每日任务
    const VIEW_AWARD_TYPE = 1;
    const THUMB_AWARD_TYPE = 2;
    const COMMENT_AWARD_TYPE = 3;
    const WANT_AWARD_TYPE = 4;
    const SHARE_AWARD_TYPE = 5;
    const ENCOURAGE_AWARD_TYPE = 6; // 每日看小视频
    const QUESTION_AWARD_TYPE = 7;
    const SING_DOUBLE_AWARD_TYPE = 8; // 签到翻倍目标奖励
    const BIND_WX_AWARD_TYPE = 9;
    const ADD_KEFU_AWARD_TYPE = 10;
    const INVITE_AWARD_TYPE = 11;
    const PUBLISH_AWARD_TYPE = 12;
    const PUBLISH_CLOCK_AWARD_TYPE = 13;
    const CLOCK_VIEW_AWARD_TYPE = 14; // 打卡浏览数奖励
    const SUBSCRIBE_AWARD_TYPE = 15; //关注公众号
    const SEARCH_AWARD_TYPE = 16; //每日搜索
    const REFRESH_AWARD_TYPE = 21; // 擦亮宝贝
    const POINT_DUE_AWARD_TYPE = 22; // 积分过期
    const WANT_FREE_AWARD_TYPE = 23; // 想要免费商品需要扣除积分
    const WHEEL_AWARD_TYPE = 61; // 大转盘抽奖
    const SIGN_AWARD_TYPE = 62; // 签到奖励
    const SING_TARGET_AWARD_TYPE = 63; // 连续签到目标奖励
    const BUSINESS_ORDER_FANLI_AWARD_TYPE = 81; // 订单返利
    const BUSINESS_VIEW_SECONDS_AWARD_TYPE = 82; // 电商平台浏览60秒
    const BUSINESS_FIRST_ORDER_AWARD_TYPE = 83; // 首单奖励
    const BUSINESS_ORDER_AWARD_TYPE = 84; // 每日一单
    const BUSINESS_ORDER_NUM_AWARD_TYPE = 85; // 订单数满多少次奖励
    const KEFU_ADD_AWARD_TYPE = 101;
    const KEFU_PLUS_AWARD_TYPE = 102;
    const RANK_AWARD_TYPE = 103; // 排行榜奖励（前三有积分奖励）


    const UID_OFFSET = 100000; // 为了bitmap从0开始；

    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;
    private $redisKey;
    private $typeId;
    private $uid;
    private $time;

    public function __construct($typeId, $uid, $time)
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $this->redisKey = RedisHelper::RK('onceOfDateRewardPoint', $typeId, date('Ymd', $time));
        $this->typeId = $typeId;
        $this->uid = $uid;
        $this->time = $time;
    }

    /**
     * 完成每日任务
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/4/2 14:26
     */
    public function finishOnceReward()
    {
        return $this->redisBaseCluster->setBit($this->redisKey, $this->uid - self::UID_OFFSET, 1);
    }

    /**
     * 是否完成每日任务
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/4/2 13:40
     */
    public function isFinishOnceReward()
    {
        return $this->redisBaseCluster->getBit($this->redisKey, $this->uid - self::UID_OFFSET);
    }

    /**
     * 发放/扣除积分
     *
     * @param $point
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/2 16:33
     */
    public function awardPoint($point = 0)
    {
        try {
            $awardTypeInfo = Yii::$app->params['awardType'][$this->typeId];
            if (!empty($point)) {
                $point = $awardTypeInfo['exp'] == 1 ? $point : -$point;
            } else {
                $point = $awardTypeInfo['exp'] == 1 ? $awardTypeInfo['point'] : -$awardTypeInfo['point'];
            }

            // 累加或累减用户redis的积分
            $userInfoKey = RedisHelper::RK('userInfo', $this->uid);
            if ($this->redisBaseCluster->exists($userInfoKey)) { // redis未过期
                if ($point < 0) { // 扣除积分时，验证积分是否足够
                    $rewardPoint = $this->redisBaseCluster->hGet($userInfoKey, 'reward_point');
                    if ($rewardPoint < abs($point)) {
                        return ToolsHelper::funcReturn('积分不足，先去赚积分吧');
                    }
                }
                $currentPoint = $this->redisBaseCluster->hIncrBy($userInfoKey, 'reward_point', $point);
            } else { // redis过期时，查询数据库
                $userPoint = UserData::find()->select(['reward_point'])->where(['uid' => $this->uid])->scalar();
                $currentPoint = $userPoint + $point;
            }

            // 发送到队列，完成数据库和积分记录
            Yii::$app->rewardPointQueue->push(
                new RewardPointJob(
                    [
                        'data' => [
                            'uid' => $this->uid,
                            'type' => $this->typeId,
                            'title' => $awardTypeInfo['title'],
                            'point' => $point,
                            'current_point' => $currentPoint,
                            'created_at' => $this->time,
                        ]
                    ]
                )
            );

            return ToolsHelper::funcReturn(
                '操作成功',
                true,
                [
                    'point' => $point,
                    'currentPoint' => $currentPoint
                ]
            );
        } catch (\Exception $e) {
            Yii::info(
                [
                    'data' => [
                        'uid' => $this->uid,
                        'type' => $this->typeId,
                        'title' => $awardTypeInfo['title'],
                        'point' => $point,
                        'created_at' => $this->time,
                    ],
                    'error' => $e->getMessage()
                ],
                'rewardPoint'
            );
            return ToolsHelper::funcReturn('操作失败');
        }
    }

    /**
     * 每日任务发放积分奖励
     *
     * @param int $point
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/2 15:28
     */
    public function onceTaskAwardPoint($point = 0)
    {
        $taskTitle = ArrayHelper::getValue(Yii::$app->params['awardType'], $this->typeId . '.title', '');
        if (!$this->isFinishOnceReward()) {
            if (!$this->finishOnceReward()) {
                $pointRes = $this->awardPoint($point);
                if ($pointRes['result']) {
                    return ToolsHelper::funcReturn($taskTitle . "任务完成，" . abs($pointRes['data']['point']) . "积分已放入账户", true, ['point' => abs($pointRes['data']['point'])]);
                }
            }
        }
        return ToolsHelper::funcReturn($taskTitle . "任务已完成", false, ['point' => 0]);
    }

    /**
     * 完成任务赚积分
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/4/6 11:14
     */
    public function getTaskList()
    {
        $awardTypeArr = Yii::$app->params['awardType'];
        foreach ($awardTypeArr as $typeId => &$value) {
            // 去除不展示任务类型
            if (empty($value['show_task'])) {
                unset($awardTypeArr[$typeId]);
                continue;
            }

            $value['id'] = $typeId;
            if (!empty($value['info'])) {
                $value['info'] = str_replace('%s', $value['point'], $value['info']);
            }

            if (!empty($value['img_url'])) {
                $value['img_url'] = ToolsHelper::getLocalImg($value['img_url']);
            }

            // 每日任务
            if ($value['type'] == self::DAILY_TASK_TYPE) {
                $redisKey = RedisHelper::RK('onceOfDateRewardPoint', $typeId, date('Ymd'));
                if ($this->redisBaseCluster->getBit($redisKey, $this->uid - self::UID_OFFSET)) {
                    unset($awardTypeArr[$typeId]);
                    continue;
                }
            } elseif ($value['type'] == self::ONCE_TASK_TYPE) { // 一次性任务
                if ($this->isFinishOnceTask($typeId)) {
                    unset($awardTypeArr[$typeId]);
                    continue;
                }
            }

            // 互斥任务，首次下单与每日一单只出现一个，优先出现首次下单任务
            if ($value['type'] == self::BUSINESS_FIRST_ORDER_AWARD_TYPE) {
                unset($awardTypeArr[self::BUSINESS_ORDER_AWARD_TYPE]);
            }
        }

        ArrayHelper::multisort($awardTypeArr, 'sort', SORT_ASC);
        return array_values($awardTypeArr);
    }

    /**
     * 获取积分记录
     *
     * @param     $uid
     * @param int $awardType
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\mongodb\ActiveRecord
     *
     * @author     xudt
     * @date-time  2021/4/6 11:48
     */
    public static function getRewardPointRecord($uid, $awardType = 0, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        MongodbRewardPointRecord::resetTableName($uid);
        $query = MongodbRewardPointRecord::find()->where(['uid' => intval($uid)]);
        if (!empty($awardType)) {
            $query->andWhere(['type' => intval($awardType)]);
        }
        $dataList = $query->orderBy('created_at desc')->offset($start)->limit($pageSize)->asArray()->all();
        if (!empty($dataList)) {
            foreach ($dataList as $key => &$value) {
                $value['created_at'] = date("Y-m-d H:i:s", $value['created_at']);
                unset($value['_id']);
            }
        }
        return $dataList;
    }

    /**
     * 跑马灯效果
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 12:11
     */
    public static function getUserAwardNotice()
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('userAwardPointRecord');
        $dataList = $redisBaseCluster->zRevRange($redisKey, 0, -1, true);
        $uidArr = [];
        $noticeData = [];
        if (!empty($dataList)) {
            foreach ($dataList as $value => $score) {
                $itemArr = json_decode($value, true);
                $uidArr[] = $itemArr['uid'];
            }

            // 批量查询数据库用户昵称
            $userService = new UserService();
            $userArr = $userService->getUserNickname($uidArr);
        }

        if (!empty($userArr)) {
            foreach ($dataList as $value => $score) {
                $itemArr = json_decode($value, true);
                $message = self::getNotice($userArr, $itemArr);
                if (empty($message)) {
                    continue;
                }
                $noticeData[] = $message;
            }
        }

        return $noticeData;
    }

    /**
     * 组成消息内容
     *
     * @param $userArr
     * @param $itemArr
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/4/11 12:03
     */
    private static function getNotice($userArr, $itemArr)
    {
        if (time() - $itemArr['created_at'] > 1800) {
            return '';
        }
        $time = ToolsHelper::getTimeStrDiffNow($itemArr['created_at']);
        $uid = $itemArr['uid'];
        if (!isset($userArr[$uid])) {
            return '';
        }
        $nickname = ToolsHelper::ellipsisStr($userArr[$uid], 6);
        switch ($itemArr['type']) {
            case RewardPointService::VIEW_AWARD_TYPE:
            case RewardPointService::THUMB_AWARD_TYPE:
            case RewardPointService::COMMENT_AWARD_TYPE:
            case RewardPointService::SHARE_AWARD_TYPE:
            case RewardPointService::WANT_AWARD_TYPE:
            case RewardPointService::ENCOURAGE_AWARD_TYPE:
            case RewardPointService::ADD_KEFU_AWARD_TYPE:
            case RewardPointService::BIND_WX_AWARD_TYPE:
            case RewardPointService::QUESTION_AWARD_TYPE:
            case RewardPointService::SING_DOUBLE_AWARD_TYPE:
            case RewardPointService::SUBSCRIBE_AWARD_TYPE:
            case RewardPointService::SEARCH_AWARD_TYPE:
            case RewardPointService::BUSINESS_ORDER_FANLI_AWARD_TYPE:
            case RewardPointService::BUSINESS_VIEW_SECONDS_AWARD_TYPE:
            case RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE:
            case RewardPointService::BUSINESS_ORDER_AWARD_TYPE:
            case RewardPointService::BUSINESS_ORDER_NUM_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"完成' . $itemArr['title'] . "获得" . $itemArr['point'] . "积分";
                break;
            case RewardPointService::INVITE_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"邀请了一位好友获得' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::PUBLISH_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"发布了一件宝贝获得' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::PUBLISH_CLOCK_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"发布网红地打卡获得' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::CLOCK_VIEW_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"打卡浏览数达成奖励' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::WHEEL_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"大转盘抽奖获得' . $itemArr['point'] . "积分";
                break;
            case $itemArr['type'] >= 30 && $itemArr['type'] <= 60:
                $message = $time . ' "' . $nickname . '"成功' . $itemArr['title'];
                break;
            case RewardPointService::SIGN_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"每日签到获得' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::SING_TARGET_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"连续签到获得' . $itemArr['point'] . "积分";
                break;
            case RewardPointService::RANK_AWARD_TYPE:
                $message = $time . ' "' . $nickname . '"获得每日排行榜奖励' . $itemArr['point'] . "积分";
                break;
            case 200: // 卖出物品加入到跑马灯
                $message = $time . ' "' . $nickname . '"卖出了' . $itemArr['title'];
                break;
            default:
                $message = '';
        }
        return $message;
    }

    /**
     * 是否完成一次性任务
     *
     * @param $type
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/5/17 10:45
     */
    public function isFinishOnceTask($type)
    {
        MongodbRewardPointRecord::resetTableName($this->uid);
        return MongodbRewardPointRecord::find()->where(['uid' => intval($this->uid), 'type' => intval($type)])->exists();
    }
}