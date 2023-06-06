<?php
/**
 * 我的好友
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/6 20:03
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\InviteService;

use Yii;

class FriendController extends BaseController
{
    const PAGESIZE = 20;
    /**
     * 邀请好友首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 06:49
     */
    public function actionInvite()
    {
        $userInfo = Yii::$app->params['userRedis'];

        //限时时间
        $start = date("m.01");
        $end = date('m.01', strtotime('last day of +1 month'));

        $inviteRuleHtmlArr = [
            [
                'title' => '微信邀请',
                'content' => '<p>1、点击小程序首页、宝贝详情页及当前页面<span class="bold">“微信邀请”或“立即邀请赚积分”</span>按钮，选择要发送的微信好友或微信群，分享完成。</p>
        <p>2、好友收到分享信息后，<span class="bold">点击进入小程序</span>，微信登录即完成邀请（若第一次登录则为注册成功，将获得积分奖励）。</p>',
            ],
            [
                'title' => '面对面邀请',
                'content' => '<p>1、点击当前页面下方<span class="bold">“面对面邀请“按钮</span>（或我的-设置（图标）-用户信息-我的二维码），跳转到我的二维码页面。</p>
        <p>2、好友打开微信<span class="bold">”扫一扫“</span>，扫描二维码后进入小程序，微信登录即完成邀请（若第一次登录则为注册成功，将获得积分奖励）。</p>',
            ],
        ];

        $shareInfo = [
            'title' => '积分免费兑换现金红包，手机充值卡，优酷、腾讯、爱奇艺视频vip会员，赶紧注册一个一起赚积分吧',
            'imageUrl' => '',
            'path' => '/pages/index/index?invite_code='.$userInfo['invite_code'],
            'params' => 'invite_code='.$userInfo['invite_code']
        ];

        return ToolsHelper::funcReturn(
            "邀请好友",
            true,
            [
                'userInfo' => $userInfo,
                'start' => $start,
                'end' => $end,
                'inviteRuleHtmlArr' => $inviteRuleHtmlArr,
                'shareInfo' => $shareInfo,
                'rewardPoint' => Yii::$app->params['awardType'][11]['point'],
            ]
        );
    }

    /**
     * 好友列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 20:06
     */
    public function actionList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $inviteService = new InviteService();
        $friendList = $inviteService->getFriendListByUid($uid,$page,$pageSize);
        return ToolsHelper::funcReturn(
            "我的好友列表",
            true,
            [
                'userInfo' => $userInfo,
                'friendList' => $friendList,
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }
}