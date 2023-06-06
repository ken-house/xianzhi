<?php
/**
 * 战队竞赛
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/25 09:48
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\RewardPointService;
use common\services\TeamService;
use Yii;

class TeamController extends BaseController
{
    const PAGESIZE = 10;

    /**
     * 战队竞赛首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 15:03
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        // 读取当期活动数据
        $teamService = new TeamService();
        $teamActivityData = $teamService->getTeamActivityData();

        // 我的战队
        $myTeamData = $teamService->myTeamData($uid);

        // 战队前三名
        $teamList = $teamService->getTeamList(1, 3);


        // 若用户已加入战队，跳转到战队详情页；若未加入，跳转到首页
        $path = '/pages/team/index/index?invite_code=' . $userInfo['invite_code'];
        $params = 'invite_code=' . $userInfo['invite_code'];
        $title = '立即加入战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～';
        if ($myTeamData['join'] == 1) {
            $path = '/pages/team/info/info?invite_code=' . $userInfo['invite_code'] . '&id=' . $myTeamData['team_id'];
            $params = 'invite_code=' . $userInfo['invite_code'] . '&id=' . $myTeamData['team_id'];
            $title = '邀请您加入' . $myTeamData['team_name'] . '战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～';
        }
        $shareInfo = [
            'title' => $title,
            'imageUrl' => 'http://cdn.xiaozhatravel.top/images/banner/team.jpg',
            'path' => $path,
            'params' => $params,
        ];

        return ToolsHelper::funcReturn(
            "战队竞赛",
            true,
            [
                'userInfo' => $userInfo,
                'teamActivityData' => $teamActivityData,
                'teamList' => $teamList,
                'shareInfo' => $shareInfo,
                'myTeamData' => $myTeamData,
            ],
        );
    }

    /**
     * 创建战队
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 15:08
     */
    public function actionCreateTeam()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $teamName = Yii::$app->request->post("team_name");
        if (empty($teamName)) {
            return ToolsHelper::funcReturn("请输入战队名称");
        }

        $teamService = new TeamService();
        return $teamService->createTeam($userInfo, $teamName);
    }

    /**
     * 战队列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 15:12
     */
    public function actionTeamList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $teamService = new TeamService();
        $myTeamData = $teamService->myTeamData($uid);

        // 战队列表
        $teamList = $teamService->getTeamList(1, 100);

        // 若用户已加入战队，跳转到战队详情页；若未加入，跳转到首页
        $path = '/pages/team/index/index?invite_code=' . $userInfo['invite_code'];
        $params = 'invite_code=' . $userInfo['invite_code'];
        $title = '立即加入战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～';
        if ($myTeamData['join'] == 1) {
            $path = '/pages/team/info/info?invite_code=' . $userInfo['invite_code'] . '&id=' . $myTeamData['team_id'];
            $params = 'invite_code=' . $userInfo['invite_code'] . '&id=' . $myTeamData['team_id'];
            $title = '邀请您加入' . $myTeamData['team_name'] . '战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～';
        }
        $shareInfo = [
            'title' => $title,
            'imageUrl' => 'http://cdn.xiaozhatravel.top/images/banner/team.jpg',
            'path' => $path,
            'params' => $params,
        ];

        return ToolsHelper::funcReturn(
            "战队列表",
            true,
            [
                'userInfo' => $userInfo,
                'teamList' => $teamList,
                'shareInfo' => $shareInfo,
                'myTeamData' => $myTeamData,
            ],
        );
    }

    /**
     * 战队详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 17:52
     */
    public function actionTeamInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $teamId = Yii::$app->request->get("team_id", 0);
        if (empty($teamId)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $teamService = new TeamService();
        $data = $teamService->getTeamInfo($teamId);

        $shareInfo = [
            'title' => '邀请您加入' . $data['teamInfo']['team_name'] . '战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～',
            'imageUrl' => 'http://cdn.xiaozhatravel.top/images/banner/team.jpg',
            'path' => '/pages/team/info/info?invite_code=' . $userInfo['invite_code'] . '&id=' . $teamId,
            'params' => 'invite_code=' . $userInfo['invite_code'] . '&id=' . $teamId,
        ];

        // 当前用户是否已加入战队
        $join = $teamService->isJoinTeam($userInfo['uid']);

        return ToolsHelper::funcReturn(
            "战队详情",
            true,
            [
                'userInfo' => $userInfo,
                'teamInfo' => $data['teamInfo'],
                'teamUserList' => $data['teamUserList'],
                'join' => $join,
                'shareInfo' => $shareInfo,
            ]
        );
    }

    /**
     * 加入战队
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 15:16
     */
    public function actionJoin()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $inviteCode = Yii::$app->request->post("invite_code", '');
        $teamId = Yii::$app->request->post("team_id", 0);
        if (empty($teamId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $teamService = new TeamService();
        return $teamService->joinTeam($userInfo, $inviteCode, $teamId);
    }

    /**
     * 领奖页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 15:27
     */
    public function actionPrize()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        // 抽奖规则
        $ruleArr[] = Yii::$app->params['teamData']['rule'][1];

        // 获胜战队
        $teamService = new TeamService();
        $winTeamData = $teamService->getWinTeamData($uid);

        // 奖品列表
        $prizeList = $teamService->prizeList($uid, $winTeamData['isWinner']);

        // 中奖用户列表
        $drawUserList = $teamService->getDrawUserList();

        // 抽奖截止时间
        $drawDeadline = $teamService->getDrawDeadline();

        $shareInfo = [
            'title' => '立即加入战队，也许你就是锦鲤，可赢百万积分，直接兑换现金红包，快来参与吧～',
            'imageUrl' => 'http://cdn.xiaozhatravel.top/images/banner/team.jpg',
            'path' => '/pages/team/prize/prize?invite_code=' . $userInfo['invite_code'],
            'params' => 'invite_code=' . $userInfo['invite_code'],
        ];

        return ToolsHelper::funcReturn(
            "领奖页面",
            true,
            [
                'userInfo' => $userInfo,
                'winTeamData' => $winTeamData,
                'prizeList' => $prizeList,
                'drawUserList' => $drawUserList,
                'ruleArr' => $ruleArr,
                'shareInfo' => $shareInfo,
                'drawDeadLine' => date("Y-m-d H:i:s", $drawDeadline),
            ]
        );
    }

    /**
     * 抽奖
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/26 20:54
     */
    public function actionDraw()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $prizeId = Yii::$app->request->post('prize_id', 0);
        if (empty($prizeId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $teamService = new TeamService();
        $myTeamData = $teamService->myTeamData($uid);
        $rank = isset($myTeamData['rank']) ? $myTeamData['rank'] : 0;
        $percent = isset($myTeamData['percent']) ? $myTeamData['percent'] : 0;

        return $teamService->drawPrize($uid, $rank, $percent, $prizeId);
    }

    /**
     * 跑马灯滚动数据
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 12:11
     */
    public function actionNotice()
    {
        $teamService = new TeamService();
        $noticeList = $teamService::getTeamActivityNotice();
        return ToolsHelper::funcReturn(
            "跑马灯滚动数据",
            true,
            [
                'noticeList' => $noticeList
            ]
        );
    }

}
