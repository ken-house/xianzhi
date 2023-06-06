<?php
/**
 * 个人信息
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/3/9 08:52
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BindService;
use common\services\RewardPointService;
use common\services\UnionService;
use common\services\UserService;

use Yii;

class UserController extends BaseController
{
    /**
     * 个人信息主页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/11 11:32
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];

        // 是否关注公众号
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);

        return ToolsHelper::funcReturn(
            '个人信息',
            true,
            [
                'userInfo' => $userInfo,
                'subscribe' => $subscribe,
            ]
        );
    }

    /**
     * 更新用户信息
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/30 10:53
     */
    public function actionUpdate()
    {
        $now = time();
        $userInfo = Yii::$app->params['userRedis'];
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);
        $uid = $userInfo['uid'];

        $nickname = Yii::$app->request->post("nickname", '');
        $avatar = Yii::$app->request->post("avatar", '');

        $userService = new UserService();
        $updateData = [
            'nickname' => $nickname,
            'avatar' => $avatar,
            'updated_at' => $now
        ];
        $res = $userService->saveUserinfo($uid, $updateData);
        if ($res) {
            return ToolsHelper::funcReturn(
                "个人信息",
                true,
                [
                    'userInfo' => array_merge($userInfo, $updateData)
                ]
            );
        }
    }

    /**
     * 生成二维码
     *
     *
     * @author     xudt
     * @date-time  2021/4/11 09:09
     */
    public function actionErweima()
    {
        $inviteCode = Yii::$app->request->get("invite_code");

        $url = Yii::$app->params['domain'] . "/wx/erweima?invite_code=" . $inviteCode;
        $qrcode = new \QRcode();
        echo $qrcode->png($url, false, 'L', 6, 1);//调用png()方法生成二维码
        die;
    }

    /**
     * 绑定微信号
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/11 12:10
     */
    public function actionBindWx()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();
        $wxPublic = Yii::$app->request->post('wx_public', 0); // 是否公开微信号
        $wxPublic = !empty($wxPublic) ? 1 : 0;
        $wx = trim(Yii::$app->request->post('wx', ''));
        if (empty($wx)) {
            return ToolsHelper::funcReturn("请输入微信号");
        }

        $userService = new UserService();
        $res = $userService->saveUserinfo(
            $uid,
            [
                'wx' => $wx,
                'wx_public' => $wxPublic,
                'updated_at' => $now
            ]
        );
        if ($res) {
            if ($wx != '' && $wxPublic == 1) {
                $rewardPointService = new RewardPointService(RewardPointService::BIND_WX_AWARD_TYPE, $uid, $now);
                if (!$rewardPointService->isFinishOnceTask(RewardPointService::BIND_WX_AWARD_TYPE)) {
                    $rewardRes = $rewardPointService->awardPoint();
                    if ($rewardRes['result']) {
                        // 解除屏蔽限制
                        if ($uid <= 100436) {
                            $userService->removeDenyUser($uid);
                        }
                        return ToolsHelper::funcReturn("绑定成功", true, ['rewardTips' => "绑定成功，" . $rewardRes['data']['point'] . "积分已放入账户"]);
                    }
                }
            }
            return ToolsHelper::funcReturn("绑定成功", true);
        }
        return ToolsHelper::funcReturn("绑定失败");
    }

    /**
     * 绑定手机号
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/9 14:34
     */
    public function actionBindPhone()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        if (!empty($userInfo['phone'])) {
            return ToolsHelper::funcReturn("您已绑定手机号，如需换绑请联系客服");
        }

        $phone = Yii::$app->request->post('phone', '');
        $verifyCode = Yii::$app->request->post('verify_code', '');

        $userService = new UserService();
        return $userService->bindPhone($uid, $phone, $verifyCode);
    }


    /**
     * 我的地址
     *
     *
     * @author     xudt
     * @date-time  2021/3/11 13:20
     */
    public function actionAddressList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $userService = new UserService();
        $addressList = $userService->getAddressList($uid);

        return ToolsHelper::funcReturn(
            "地址列表",
            true,
            [
                'addressList' => $addressList
            ]
        );
    }

    /**
     * 地址详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/18 14:40
     */
    public function actionAddressInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $id = Yii::$app->request->get('id');

        $userService = new UserService();
        $addressInfo = $userService->getAddressInfo($uid, $id);
        if (empty($addressInfo)) {
            return ToolsHelper::funcReturn("非法操作");
        }

        return ToolsHelper::funcReturn(
            "地址详情",
            true,
            [
                'addressInfo' => $addressInfo
            ]
        );
    }


    /**
     * 地址保存
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/3/11 13:59
     */
    public function actionAddressSave()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $postData = Yii::$app->request->post();

        $userService = new UserService();
        if (empty($postData['id'])) {
            return $userService->addAddress($uid, $postData);
        } else {
            return $userService->editAddress($uid, $postData);
        }
    }


    /**
     * 删除地址
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/3/11 13:59
     */
    public function actionAddressDelete()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $id = Yii::$app->request->post('id');

        $userService = new UserService();
        return $userService->deleteAddress($uid, $id);
    }

    /**
     * 举报用户微信错误
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/6/1 16:20
     */
    public function actionReport()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $reportUid = Yii::$app->request->post('uid');

        $userService = new UserService();
        return $userService->reportUserWx($userInfo, $reportUid);
    }

    /**
     * 关联首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/20 20:23
     */
    public function actionUnionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $content = '<p class="rule-item iconfont">点我想要，推送消息通知提醒卖家；</p>
                    <p class="rule-item iconfont">回复消息，未读将发消息通知提醒；</p>
                    <p class="rule-item iconfont">物品审核，下发审核结果消息通知；</p>
                    <p class="rule-item iconfont">提现消息，下发提现结果消息通知；</p>
                    <p class="rule-item iconfont">返利消息，下发返利到账消息通知；</p>
                    ';
        return ToolsHelper::funcReturn(
            "关联公众号",
            true,
            [
                'userInfo' => $userInfo,
                'content' => $content,
            ]
        );
    }

    /**
     * 帐号绑定
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/19 10:52
     */
    public function actionUnion()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $secretKey = Yii::$app->request->post('secret_key', '');
        if (empty($secretKey)) {
            return ToolsHelper::funcReturn("请输入动态码");
        }
        $now = time();

        $unionService = new UnionService();
        $unionRes = $unionService->unionRelation($secretKey, $userInfo['wx_openid']);
        if ($unionRes['result']) {
            // 完成任务，发放奖励
            $rewardPointService = new RewardPointService(RewardPointService::SUBSCRIBE_AWARD_TYPE, $userInfo['uid'], $now);
            if (!$rewardPointService->isFinishOnceTask(RewardPointService::SUBSCRIBE_AWARD_TYPE)) {
                $rewardRes = $rewardPointService->awardPoint();
                if ($rewardRes['result']) {
                    // 解除屏蔽限制
                    if ($userInfo['uid'] <= 100436) {
                        $userService = new UserService();
                        $userService->removeDenyUser($userInfo['uid']);
                    }
                    return ToolsHelper::funcReturn("关联成功", true, ['rewardTips' => "关联成功，" . $rewardRes['data']['point'] . "积分已放入账户"]);
                }
            }
        }
        return $unionRes;
    }
}