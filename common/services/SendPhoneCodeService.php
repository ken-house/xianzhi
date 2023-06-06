<?php
/**
 * 发送手机短信服务
 *
 * @author xudt
 * @date   : 2019/11/2 16:31
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\User;
use common\services\sms\abstracts\SendCodeAbstract;
use common\services\sms\alibaba\AlibabaService;
use common\services\sms\interfaces\SendCodeInterface;

class SendPhoneCodeService extends SendCodeAbstract implements SendCodeInterface
{
    const CODETRYCOUNT = 5;   //短信重试次数为5次

    /**
     * 发送验证码
     *
     * @param $phone
     * @param $type
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/2 17:06
     *
     */
    public function sendCode($phone, $type)
    {
        $verifyPhoneRes = ToolsHelper::VerfiyPhone($phone);
        if (!$verifyPhoneRes) {
            return ToolsHelper::funcReturn("手机号不正确");
        }
        // 判断手机号是否已绑定
        $phoneExist = User::find()->where(['phone' => $phone])->limit(1)->exists();
        if ($phoneExist) {
            return ToolsHelper::funcReturn("手机号已被占用");
        }
        //发送短信验证码
        $sendRes = $this->send($phone, $type);
        if (!$sendRes['result']) {
            return ToolsHelper::funcReturn("发送验证码失败");
        }
        $redisKey = RedisHelper::RK('phoneCode', $type, $phone);
        $saveRes = $this->saveCodeToRedis($redisKey, $sendRes['data']['code']);
        if (!$saveRes['result']) {
            return ToolsHelper::funcReturn("发送验证码失败，服务器内部错误");
        }
        return ToolsHelper::funcReturn("发送验证码成功", true);
    }

    /**
     * 验证短信验证码
     *
     * @param $phone
     * @param $type
     * @param $code
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/2 17:29
     *
     */
    public function verifyCode($phone, $type, $code)
    {
        $phoneVerifyRes = ToolsHelper::VerfiyPhone($phone);
        if (!$phoneVerifyRes) {
            return ToolsHelper::funcReturn("手机号不正确");
        }
        $phoneCodeVerifyRes = ToolsHelper::VerfiyPhoneCode($code);
        if (!$phoneCodeVerifyRes) {
            return ToolsHelper::funcReturn("验证码不正确");
        }
        $redisKey = RedisHelper::RK('phoneCode', $type, $phone);
        $codeRes = $this->getCodeFromRedis($redisKey);
        if (!$codeRes['result']) {
            return ToolsHelper::funcReturn("验证码已失效，请重新发送验证码");
        }
        if ($codeRes['data']['try_count'] >= self::CODETRYCOUNT) {
            return ToolsHelper::funcReturn("验证码重试次数过多，请重新发送验证码");
        }

        if ($code != $codeRes['data']['verify_code']) {
            //更新验证码重试次数
            $this->incrTryCountToRedis($redisKey);
            return ToolsHelper::funcReturn("验证码错误");
        }
        return ToolsHelper::funcReturn("验证成功", true);
    }

    /**
     * 发送短信验证码
     *
     * @param $phone
     * @param $type
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/2 16:53
     *
     */
    public function send($phone, $type)
    {
        $templateCode = "";
        switch ($type) {
            case 1:  // 注册登录
                $templateCode = "SMS_204105589";
                break;
            case 2: // 绑定手机
                $templateCode = "SMS_204115559";
                break;
            case 3: // 换绑前验证手机
                $templateCode = "SMS_204125567";
                break;
        }

        $code = ToolsHelper::getSmsCode();
        if (empty($templateCode) || empty($code)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $templateParam = json_encode(['code' => $code]);
        $alibabaService = new AlibabaService();
        $sendRes = $alibabaService->sendSms($phone, $templateCode, $templateParam);
        if (!$sendRes['result']) {
            return ToolsHelper::funcReturn("发送失败");
        }
        return ToolsHelper::funcReturn("发送成功", true, ['code' => $code]);
    }

    /**
     * 清空验证码的redis
     *
     * @param $phone
     * @param $type
     *
     * @author   xudt
     * @dateTime 2020/2/26 16:30
     */
    public function clear($phone, $type)
    {
        $redisKey = RedisHelper::RK('phoneCode', $type, $phone);
        $this->delPhoneCodeRedis($redisKey);
    }


}