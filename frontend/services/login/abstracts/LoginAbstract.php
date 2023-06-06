<?php
/**
 * @author xudt
 * @date   : 2019/11/2 11:11
 */

namespace frontend\services\login\abstracts;

use common\components\JWTComponent;
use common\helpers\ToolsHelper;
use common\services\classes\SendPhoneCodeService;
use common\services\CoinService;
use common\services\InviteService;
use common\services\TaskService;
use common\services\UserService;
use common\services\WechatService;
use console\services\jobs\CoinJob;
use Yii;

abstract class LoginAbstract
{
    const SMSCODETYPE = 1;

    /**
     * 验证手机验证码登录
     *
     * @param $phone
     * @param $verifyCode
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/2 17:36
     *
     */
    protected function VerfiyPhoneCodeLogin($phone, $verifyCode)
    {
        $sendPhoneCodeService = new SendPhoneCodeService();
        $verifyCodeRes = $sendPhoneCodeService->verifyCode($phone, self::SMSCODETYPE, $verifyCode);
        return $verifyCodeRes;
    }

    /**
     * 登录成功后清除手机短信验证码redis
     *
     * @param $phone
     *
     * @author   xudt
     * @dateTime 2020/2/26 16:33
     *
     */
    protected function clearPhoneCodeRedis($phone)
    {
        $sendPhoneCodeService = new SendPhoneCodeService();
        $sendPhoneCodeService->clear($phone, self::SMSCODETYPE);
    }


    /**
     * 密码验证登录
     *
     * @param $phone
     * @param $password
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/11 19:23
     *
     */
    protected function VerfiyPhonePwdLogin($phone, $password)
    {
        $phoneVerifyRes = ToolsHelper::VerfiyPhone($phone);
        if (!$phoneVerifyRes) {
            return ToolsHelper::funcReturn("手机号不正确");
        }

        $passwordVerifyRes = ToolsHelper::VerfiyPassword($password);
        if (!$passwordVerifyRes) {
            return ToolsHelper::funcReturn("密码不正确");
        }

        $userService = new UserService();
        $verifyPwdRes = $userService->verifyPassword($phone, $password);
        return $verifyPwdRes;
    }

    /**
     * 小程序微信登录获取用户信息
     *
     * @param array $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/5/17 10:11
     */
    public function wxLogin($data = [])
    {
        if (empty($data['session_key']) || empty($data['encryptedData']) || empty($data['iv'])) {
            return ToolsHelper::funcReturn("微信请求失败，请重试");
        }

        if (strlen($data['session_key']) != 24 || strlen($data['iv']) != 24) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $wechatService = new WechatService();
        $userRes = $wechatService->getWxUserInfo($data);
        return $userRes;
    }


    /**
     * 用户手机注册
     *
     * @param $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/14 19:54
     *
     */
    public function userRegisterByPhone($data)
    {
        $userService = new UserService();
        //判断是否为注销过用户
        $isDestoryedUser = $userService->isDestoryedUser($data['phone']);
        if ($isDestoryedUser) { //注销过的用户注册，不给新手奖励，不可绑定邀请用户，不可完成一次性任务
            return $this->destoryedUserRegisterByPhone($data);
        }

        $registerRes = $userService->userRegByPhone($data['phone']);
        if (!$registerRes['result']) {
            return ToolsHelper::funcReturn("注册失败");
        }
        $userInfo = $registerRes['data'];
        //写入到redis
        if (!empty($data['invite_code'])) { //绑定好友关系
            $inviteService = new InviteService();
            $bindRes = $inviteService->bindFriendShip($userInfo['uid'], $userInfo, $data['invite_code']);
            if ($bindRes['result']) {
                $userInfo = $bindRes['data'];
            }
        }
        $userService->updateUserInfoToRedis($userInfo);
        //生成jwt
        $userInfo['iss'] = '/api/login/login?login_type=1';
        $JWTComponent = new JWTComponent();
        $userInfo['token'] = $JWTComponent->encodeToken($userInfo);
        if (empty($userInfo['token'])) {
            return ToolsHelper::funcReturn("注册登录失败");
        }

        if (intval($userInfo['coin_num']) > 0) {
            //更新用户今日获得金币数
            $userService->incrUserTodayData(intval($userInfo['uid']), 'coinNum', intval($userInfo['coin_num']));

            //记录用户操作金币集合
            $userService->addCoinOperatorUserOfDay(intval($userInfo['uid']));

            //金币记录推送到队列
            $queueData = [
                'uid' => intval($userInfo['uid']),
                'coin' => intval($userInfo['coin_num']),
                'current_coin' => intval($userInfo['coin_num']),
                'type' => 10000,
                'created_at' => time()
            ];
            Yii::$app->coinQueue->push(new CoinJob(['data' => $queueData]));
        }
        return ToolsHelper::funcReturn("注册登录成功", true, $userInfo);
    }

    /**
     * 注销过的用户进行重新注册
     *
     * @param $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/3/27 14:22
     *
     */
    public function destoryedUserRegisterByPhone($data)
    {
        $userService = new UserService();
        $registerRes = $userService->userRegByPhone($data['phone'], true);
        if (!$registerRes['result']) {
            return ToolsHelper::funcReturn("注册失败");
        }
        $userInfo = $registerRes['data'];

        $userService->updateUserInfoToRedis($userInfo);

        //生成jwt
        $userInfo['iss'] = '/api/login/login?login_type=1';
        $JWTComponent = new JWTComponent();
        $userInfo['token'] = $JWTComponent->encodeToken($userInfo);
        if (empty($userInfo['token'])) {
            return ToolsHelper::funcReturn("注册登录失败");
        }

        //将一次性任务标识为已完成
        $taskService = new TaskService($userInfo['uid']);
        $taskService->finishAllOnceTask();

        return ToolsHelper::funcReturn("注册登录成功", true, $userInfo);
    }

    /**
     * 手机登录
     *
     * @param $userInfo
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/16 15:03
     *
     */
    public function userLoginByPhone($userInfo)
    {
        if (!$userInfo['status']) {
            return ToolsHelper::funcReturn("登录失败，账号已禁用");
        }
        $userService = new UserService();
        $loginRes = $userService->userLogin($userInfo);
        if (!$loginRes['result']) {
            return ToolsHelper::funcReturn("登录失败");
        }
        $userInfoData = $loginRes['data'];
        //更新到redis
        $userService->updateUserInfoToRedis($userInfoData);
        //生成jwt
        $userInfoData['iss'] = '/api/login/login';
        $JWTComponent = new JWTComponent();
        $userInfoData['token'] = $JWTComponent->encodeToken($userInfoData);
        if (empty($userInfoData['token'])) {
            return ToolsHelper::funcReturn("登录失败");
        }
        return ToolsHelper::funcReturn("登录成功", true, $userInfoData);
    }

    /**
     * 微信注册
     *
     * @param $wxUserInfo
     * @param $inviteCode
     *
     * @return array
     * @throws \Exception
     *
     * @author     xudt
     * @date-time  2021/4/9 15:00
     */
    public function userRegisterByWechat($wxUserInfo, $inviteCode)
    {
        $userService = new UserService();
        $registerRes = $userService->UserRegByWechat($wxUserInfo);
        if (!$registerRes['result']) {
            return ToolsHelper::funcReturn("注册失败");
        }
        $userInfo = $registerRes['data'];
        //写入到redis
        if (!empty($inviteCode)) { //绑定好友关系
            $inviteService = new InviteService();
            $bindRes = $inviteService->bindFriendShip($userInfo['uid'], $userInfo, $inviteCode);
            if ($bindRes['result']) {
                $userInfo = $bindRes['data'];
            }
        }
        //写入到redis
        $userService->updateUserInfoToRedis($userInfo);
        //生成jwt
        $userInfo['iss'] = '/api/login/login?login_type=3';
        $JWTComponent = new JWTComponent();
        $userInfo['token'] = $JWTComponent->encodeToken($userInfo);
        if (empty($userInfo['token'])) {
            return ToolsHelper::funcReturn("注册登录失败");
        }
        return ToolsHelper::funcReturn("注册登录成功", true, $userInfo);
    }

    /**
     * 微信登录
     *
     * @param $userInfo
     * @param $wxUserInfo
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/17 12:23
     *
     */
    public function userLoginByWechat($userInfo)
    {
        if (!$userInfo['status']) {
            return ToolsHelper::funcReturn("登录失败，账号已禁用");
        }
        $userService = new UserService();
        $loginRes = $userService->userLogin($userInfo);
        if (!$loginRes['result']) {
            return ToolsHelper::funcReturn("登录失败");
        }
        $userInfoData = $loginRes['data'];
        //更新到redis
        $userService->updateUserInfoToRedis($userInfoData);
        //生成jwt
        $userInfoData['iss'] = '/api/login/login?login_type=3';
        $JWTComponent = new JWTComponent();
        $userInfoData['token'] = $JWTComponent->encodeToken($userInfoData);
        if (empty($userInfoData['token'])) {
            return ToolsHelper::funcReturn("登录失败");
        }
        return ToolsHelper::funcReturn("登录成功", true, $userInfoData);
    }
}