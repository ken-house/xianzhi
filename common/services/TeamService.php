<?php
/**
 * 战队竞赛服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/25 10:27
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\TeamActivity;
use common\models\TeamList;
use common\models\TeamPrize;
use common\models\TeamUser;
use common\models\User;
use common\models\UserData;
use Yii;

class TeamService
{
    const UID_OFFSET = 100000; // 为了bitmap从0开始；
    private $prizeStatusArr = [ // 抽奖状态
        0 => '抽奖',
        1 => '中奖了',
        2 => '已开奖',
        3 => '已抽奖',
        4 => '不可抽奖',
    ];

    /**
     * 活动本期数据
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/8/25 10:40
     */
    public function getTeamActivityData()
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $teamActivityData = TeamActivity::find()->where(['period_num' => $currentTeamData['period_num']])->asArray()->one();
        if (!empty($teamActivityData)) {
            $teamActivityData['start_date'] = date("Y.m.d", $teamActivityData['start_at']);
            $teamActivityData['end_date'] = date("Y.m.d H:i", $teamActivityData['end_at']);
            $teamActivityData['status'] = time() > $teamActivityData['end_at'] ? 0 : 1;
        }
        $teamActivityData['reward_point'] = number_format($teamActivityData['reward_point']);
        $teamActivityData['team_count'] = TeamList::find()->where(['period_num' => $currentTeamData['period_num']])->count();
        return array_merge($teamActivityData, $currentTeamData);
    }

    /**
     * 战队列表
     *
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/8/25 11:06
     */
    public function getTeamList($page = 1, $pageSize = 10)
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $start = ($page - 1) * $pageSize;
        $teamList = TeamList::find()->where(['period_num' => $currentTeamData['period_num']])->orderBy("team_user_num desc")->offset($start)->limit($pageSize)->asArray()->all();
        if (!empty($teamList)) {
            $rank = 1;
            foreach ($teamList as $key => &$value) {
                $teamId = $value['id'];
                $value['rank'] = $start + $rank;
                $value['user_list'] = TeamUser::find()->where(['team_id' => $teamId])->orderBy('id asc')->limit(3)->asArray()->all();
                $rank++;
            }
        }
        return $teamList;
    }

    /**
     * 获取本期获胜战队
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/9/5 20:32
     */
    public function getWinTeamData($uid)
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $winTeamData = TeamList::find()->where(['period_num' => $currentTeamData['period_num']])->orderBy("team_user_num desc")->limit(1)->asArray()->one();

        // 战队成员列表
        $winTeamData['user_list'] = TeamUser::find()->where(['team_id' => $winTeamData['id']])->orderBy('id asc')->limit(5)->asArray()->all();

        // 当前用户是否在战队内
        $isWinner = TeamUser::find()->where(['team_id' => $winTeamData['id'], 'uid' => $uid])->limit(1)->exists();
        $winTeamData['isWinner'] = $isWinner ? 1 : 0;
        if ($isWinner) {
            // 我邀请的战队成员个数
            $winTeamData['inviteUserNum'] = TeamUser::find()->where(['team_id' => $winTeamData['id'], 'invite_uid' => $uid])->count();

            // 战队总被邀请人数
            $winTeamData['totalInviteUserNum'] = TeamUser::find()->where(['team_id' => $winTeamData['id']])->andWhere(['!=', 'invite_uid', 0])->count();

            $winTeamData['percent'] = !empty($winTeamData['totalInviteUserNum']) ? round($winTeamData['inviteUserNum'] / $winTeamData['totalInviteUserNum'] * 100, 4) : 0;
        }
        return $winTeamData;
    }

    /**
     * 我本期所在战队数据
     *
     * @param $uid
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/25 11:36
     */
    public function myTeamData($uid)
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $data = TeamUser::find()->where(['period_num' => $currentTeamData['period_num'], 'uid' => $uid])->asArray()->one();
        if (empty($data)) {
            $data['join'] = 0;
            return $data;
        }
        $data['id'] = $data['team_id'];
        $data['join'] = 1;

        // 我邀请的战队成员个数
        $data['inviteUserNum'] = TeamUser::find()->where(['team_id' => $data['team_id'], 'invite_uid' => $uid])->count();

        // 战队总被邀请人数
        $data['totalInviteUserNum'] = TeamUser::find()->where(['team_id' => $data['team_id']])->andWhere(['!=', 'invite_uid', 0])->count();

        // 战队成员列表
        $data['user_list'] = TeamUser::find()->where(['team_id' => $data['team_id']])->orderBy('id asc')->limit(5)->asArray()->all();

        // 战队数据
        $teamInfo = TeamList::find()->where(['id' => $data['team_id']])->asArray()->one();
        $data['team_user_num'] = $teamInfo['team_user_num'];
        $data['team_name'] = $teamInfo['team_name'];

        // 战队排名
        $tmpRank = TeamList::find()->where(['period_num' => $currentTeamData['period_num']])->where(['>', 'team_user_num', $teamInfo['team_user_num']])->count();
        $data['rank'] = $tmpRank + 1;

        // 中奖概率
        $data['percent'] = !empty($data['totalInviteUserNum']) ? round($data['inviteUserNum'] / $data['totalInviteUserNum'] * 100, 4) : 0;

        return $data;
    }

    /**
     * 战队详情
     *
     * @param $teamId
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/8/26 18:22
     */
    public function getTeamInfo($teamId)
    {
        // 战队数据
        $data['teamInfo'] = TeamList::find()->where(['id' => $teamId])->asArray()->one();

        // 战队排名
        $tmpRank = TeamList::find()->where(['period_num' => $data['teamInfo']['period_num']])->where(['>', 'team_user_num', $data['teamInfo']['team_user_num']])->count();
        $data['teamInfo']['rank'] = $tmpRank + 1;

        // 战队成员邀请好友数
        $teamUserInviteList = TeamUser::find()->select(['count(*) as invite_num'])->indexBy('invite_uid')->where(['team_id' => $teamId])->andWhere(['<>', 'invite_uid', 0])->groupBy('invite_uid')->column();
        $totalInviteUserNum = array_sum($teamUserInviteList);
        $data['teamInfo']['totalInviteUserNum'] = $totalInviteUserNum;

        // 战队成员
        $teamUserList = TeamUser::find()->where(['team_id' => $teamId])->orderBy('id asc')->asArray()->all();
        if (!empty($teamUserList)) {
            foreach ($teamUserList as $key => &$value) {
                $value['invite_num'] = isset($teamUserInviteList[$value['uid']]) ? $teamUserInviteList[$value['uid']] : 0;
                $value['percent'] = !empty($totalInviteUserNum) ? round($value['invite_num'] / $totalInviteUserNum * 100, 4) : 0;
            }
        }
        $data['teamUserList'] = $teamUserList;

        return $data;
    }

    /**
     * 用户是否已加入战队
     *
     * @param $uid
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/8 11:57
     */
    public function isJoinTeam($uid)
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $data = TeamUser::find()->where(['period_num' => $currentTeamData['period_num'], 'uid' => $uid])->asArray()->one();
        if (!empty($data)) {
            return 1;
        }
        return 0;
    }


    /**
     * 创建战队
     *
     * @param $userInfo
     * @param $teamName
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/25 14:02
     */
    public function createTeam($userInfo, $teamName)
    {
        $now = time();
        $currentTeamData = Yii::$app->params['teamData'];
        $myTeamData = TeamUser::find()->where(['period_num' => $currentTeamData['period_num'], 'uid' => $userInfo['uid']])->asArray()->one();
        if (!empty($myTeamData)) {
            return ToolsHelper::funcReturn("本期您已参与战队，不可创建新战队");
        }
        // 判断活动是否结束
        $teamActivityModel = TeamActivity::find()->where(['period_num' => $currentTeamData['period_num']])->one();
        if (!($teamActivityModel['start_at'] <= $now && $teamActivityModel['end_at'] >= $now)) {
            return ToolsHelper::funcReturn("本期活动结束，敬请关注下一期活动");
        }

        // 更新本期数据
        $rewardPoint = 100;
        if ($teamActivityModel['start_at'] <= $userInfo['created_at'] && $teamActivityModel['end_at'] >= $userInfo['created_at']) {
            $rewardPoint = 1000;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 写入战队列表
            $teamListModel = new TeamList();
            $teamListModel->team_name = $teamName;
            $teamListModel->team_user_num = 1;
            $teamListModel->period_num = $currentTeamData['period_num'];
            $teamListModel->created_at = $now;
            if ($teamListModel->save()) {
                // 写入战队成员表
                $teamUserModel = new TeamUser();
                $teamUserModel->uid = $userInfo['uid'];
                $teamUserModel->nickname = $userInfo['nickname'];
                $teamUserModel->gender = $userInfo['gender'];
                $teamUserModel->avatar = $userInfo['avatar'];
                $teamUserModel->team_id = $teamListModel->id;
                $teamUserModel->period_num = $currentTeamData['period_num'];
                $teamUserModel->reward_point = $rewardPoint;
                $teamUserModel->created_at = $now;
                $res1 = $teamUserModel->save();

                $teamActivityModel->user_num += 1;
                $teamActivityModel->reward_point += $rewardPoint;
                $teamActivityModel->updated_at = $now;
                $res2 = $teamActivityModel->save();

                if ($res1 && $res2) {
                    $transaction->commit();
                    return ToolsHelper::funcReturn("创建战队成功", true, ['id' => $teamListModel->id]);
                }
            }
            $transaction->rollBack();
            return ToolsHelper::funcReturn("创建战队失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ToolsHelper::funcReturn("创建战队失败");
        }
        return ToolsHelper::funcReturn("创建战队失败");
    }


    /**
     * 加入战队
     *
     * @param $userInfo
     * @param $inviteCode
     * @param $teamId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/25 14:18
     */
    public function joinTeam($userInfo, $inviteCode, $teamId)
    {
        $now = time();
        $teamInfo = TeamList::find()->where(['id' => $teamId])->one();
        if (empty($teamInfo)) {
            return ToolsHelper::funcReturn("不存在该战队");
        }
        // 判断活动是否结束
        $teamActivityModel = TeamActivity::find()->where(['period_num' => $teamInfo['period_num']])->one();
        if (!($teamActivityModel['start_at'] <= $now && $teamActivityModel['end_at'] >= $now)) {
            return ToolsHelper::funcReturn("本期活动结束，敬请关注下一期活动");
        }

        // 判断用户是否已参与本期活动
        $isJoin = TeamUser::find()->where(['period_num' => $teamInfo['period_num'], 'uid' => $userInfo['uid']])->limit(1)->exists();
        if ($isJoin) {
            return ToolsHelper::funcReturn("您已参与本期活动");
        }


        // 邀请者存在且必须在战队中
        if (!empty($inviteCode)) {
            $inviteUid = UserData::find()->select(['uid'])->where(['invite_code' => $inviteCode])->scalar();
            if (!empty($inviteUid)) {
                $inviteUidInTeamExist = TeamUser::find()->where(['period_num' => $teamInfo['period_num'], 'team_id' => $teamId, 'uid' => $inviteUid])->limit(1)->exists();
                if (!$inviteUidInTeamExist) {
                    $inviteUid = 0;
                }
            }
        }
        if (empty($inviteUid)) {
            $inviteUid = 0;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 更新本期数据
            $rewardPoint = 100;
            if ($teamActivityModel['start_at'] <= $userInfo['created_at'] && $teamActivityModel['end_at'] >= $userInfo['created_at']) {
                $rewardPoint = 1000;
            }

            // 写入战队成员表
            $teamUserModel = new TeamUser();
            $teamUserModel->uid = $userInfo['uid'];
            $teamUserModel->invite_uid = $inviteUid;
            $teamUserModel->nickname = $userInfo['nickname'];
            $teamUserModel->gender = $userInfo['gender'];
            $teamUserModel->avatar = $userInfo['avatar'];
            $teamUserModel->team_id = $teamId;
            $teamUserModel->period_num = $teamInfo['period_num'];
            $teamUserModel->reward_point = $rewardPoint;
            $teamUserModel->created_at = $now;
            if ($teamUserModel->save()) {
                // 更新战队数据
                $teamInfo->team_user_num += 1;
                $teamInfo->updated_at = $now;
                $res1 = $teamInfo->save();

                $teamActivityModel->user_num += 1;
                $teamActivityModel->reward_point += $rewardPoint;
                $teamActivityModel->updated_at = $now;
                $res2 = $teamActivityModel->save();

                if ($res1 && $res2) {
                    $transaction->commit();
                    return ToolsHelper::funcReturn("加入战队成功", true, ['invite_uid' => $inviteUid]);
                }
            }
            $transaction->rollBack();
            return ToolsHelper::funcReturn("加入战队失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ToolsHelper::funcReturn("加入战队失败");
        }
        return ToolsHelper::funcReturn("加入战队失败");
    }

    /**
     * 奖品列表
     *
     * @param $uid
     * @param $isWinner
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/8/25 17:36
     */
    public function prizeList($uid, $isWinner = 0)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $currentTeamData = Yii::$app->params['teamData'];
        $teamPrizeList = TeamPrize::find()->where(['period_num' => $currentTeamData['period_num']])->orderBy("id asc")->asArray()->all();
        foreach ($teamPrizeList as $key => &$value) {
            if ($isWinner == 1) { // 第一名可抽奖
                $value['status'] = 0; // 未开奖
                $redisKey = RedisHelper::RK("drawTeamPrize", $value['id']);
                $isOver = $redisBaseCluster->getBit($redisKey, $uid - self::UID_OFFSET);
                if ($isOver) {
                    $value['status'] = 3; // 已抽奖
                }
                if (!empty($value['uid'])) {
                    $value['status'] = 2; // 已开奖
                    if ($value['uid'] == $uid) {
                        $value['status'] = 1; // 中奖了
                    }
                }
            } else {
                $value['status'] = 4; // 不可抽奖
            }

            $value['status_name'] = $this->prizeStatusArr[$value['status']];
        }
        return $teamPrizeList;
    }


    /**
     * 中奖列表
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/8/25 19:14
     */
    public function getDrawUserList()
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $teamPrizeList = TeamPrize::find()->where(['period_num' => $currentTeamData['period_num']])->andWhere(['!=', 'uid', 0])->orderBy("updated_at desc")->asArray()->all();
        foreach ($teamPrizeList as $key => &$value) {
            $userInfo = User::find()->where(['id' => $value['uid']])->asArray()->one();
            $value['nickname'] = $userInfo['nickname'];
            $value['avatar'] = $userInfo['avatar'];
            $value['gender'] = $userInfo['gender'];
            $value['draw_time'] = date("Y-m-d H:i:s", $value['updated_at']);
        }
        return $teamPrizeList;
    }

    /**
     * 抽奖
     *
     * @param $uid
     * @param $percent
     * @param $rank
     * @param $prizeId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/25 17:37
     */
    public function drawPrize($uid, $rank, $percent, $prizeId)
    {
        $now = time();
        if ($rank != 1) {
            return ToolsHelper::funcReturn("您的战队未获得本期胜利");
        }

        // 写入redis，用户已抽奖
        $redisKey = RedisHelper::RK("drawTeamPrize", $prizeId);
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $isOver = $redisBaseCluster->setBit($redisKey, $uid - self::UID_OFFSET, 1);
        if ($isOver) {
            return ToolsHelper::funcReturn("您已抽奖，不可重复抽奖");
        }

        // 领奖时间已过
        if ($now > $this->getDrawDeadline()) {
            return ToolsHelper::funcReturn("抽奖已截止");
        }

        // 该奖品已被人抽中
        $teamPrizeModel = TeamPrize::find()->where(['id' => $prizeId])->one();
        if (!empty($teamPrizeModel->uid)) {
            return ToolsHelper::funcReturn("未中奖");
        }

        // 若用户已中奖，则不可中其他奖
        $exist = TeamPrize::find()->where(['period_num' => $teamPrizeModel->period_num, 'uid' => $uid])->exists();
        if ($exist) {
            return ToolsHelper::funcReturn("未中奖");
        }

        $num = rand(1, 10000);
        Yii::info(['uid' => $uid, 'percent' => $percent, 'num' => $num], "trace");
        if ($num >= 1 && $num <= $percent * 100) { // 中奖了
            // 更改中奖
            $teamPrizeModel->uid = $uid;
            $teamPrizeModel->updated_at = $now;
            if ($teamPrizeModel->save()) {
                return ToolsHelper::funcReturn("中奖了", true, ['rewardTips' => '恭喜您中奖了！请点击右侧"联系客服"，领取现金红包']);
            }
        }
        return ToolsHelper::funcReturn("未中奖");
    }

    /**
     * 截止时间
     *
     * @return false|float|int|string|null
     *
     * @author     xudt
     * @date-time  2021/9/7 11:10
     */
    public function getDrawDeadline()
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $endAt = TeamActivity::find()->select(['end_at'])->where(['period_num' => $currentTeamData['period_num']])->scalar();
        return $endAt + 86400 * 2;
    }


    /**
     * 获取跑马灯数据
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/9/6 10:52
     */
    public function getTeamActivityNotice()
    {
        $currentTeamData = Yii::$app->params['teamData'];
        $dataList = TeamUser::find()->where(['period_num' => $currentTeamData['period_num']])->where(['>=', 'created_at', time() - 1800])->asArray()->all();
        $inviteUidArr = [];
        $noticeData = [];
        $inviteUserArr = [];
        if (!empty($dataList)) {
            foreach ($dataList as $value) {
                if (!empty($value['invite_uid'])) {
                    $inviteUidArr[] = $value['invite_uid'];
                }
            }
            // 批量查询数据库用户昵称
            if (!empty($inviteUidArr)) {
                $userService = new UserService();
                $inviteUserArr = $userService->getUserNickname($inviteUidArr);
            }

            foreach ($dataList as $value) {
                $message = self::getNotice($inviteUserArr, $value);
                if (empty($message)) {
                    continue;
                }
                $noticeData[] = $message;
            }
        }

        return $noticeData;
    }

    /**
     * 生成跑马灯消息
     *
     * @param $inviteUserArr
     * @param $itemArr
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/9/6 10:52
     */
    private function getNotice($inviteUserArr, $itemArr)
    {
        $time = ToolsHelper::getTimeStrDiffNow($itemArr['created_at']);
        $inviteUid = $itemArr['invite_uid'];
        $nickname = ToolsHelper::ellipsisStr($itemArr['nickname'], 6);
        if (!empty($inviteUid)) {
            $inviteNickname = ToolsHelper::ellipsisStr($inviteUserArr[$inviteUid], 6);
            $message = $time . ' "' . $inviteNickname . '"邀请' . $nickname . '加入战队奖池增加' . $itemArr['reward_point'] . "积分";
        } else {
            $message = $time . ' "' . $nickname . '加入战队奖池增加' . $itemArr['reward_point'] . "积分";
        }
        return $message;
    }
}