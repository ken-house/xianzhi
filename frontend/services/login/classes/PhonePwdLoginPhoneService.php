<?php
/**
 * @author xudt
 * @date   : 2019/11/11 18:49
 */
namespace frontend\services\login\classes;
use common\helpers\ToolsHelper;
use frontend\services\login\abstracts\LoginAbstract;
use frontend\services\login\interfaces\LoginPhoneInterface;

class PhonePwdLoginPhoneService extends LoginAbstract implements LoginPhoneInterface
{
    /**
     * 手机密码登录
     * @author   xudt
     * @dateTime 2019/11/17 10:33
     * @param array $data
     *
     * @return array
     */
    public function login($data = [])
    {
        //验证手机号、密码是否正确
        $verifyRes = $this->VerfiyPhonePwdLogin($data['phone'], $data['password']);
        if (!$verifyRes['result']) {
            return $verifyRes;
        }

        //开始登录
        $loginRes = $this->userLoginByPhone($verifyRes['data']);
        if (!$loginRes['result']) {
            return ToolsHelper::funcReturn($loginRes['message']);
        }
        $data = $loginRes['data'];

        return ToolsHelper::funcReturn("登录成功", true, $data);
    }

}