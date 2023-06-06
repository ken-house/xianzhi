<?php
/**
 * 手机验证码登录
 *
 * @author xudt
 * @date   : 2019/11/2 10:21
 */

namespace frontend\services\login\classes;

use common\helpers\ToolsHelper;
use common\services\CoinService;
use common\services\MessageService;
use common\services\TaskService;
use common\services\UserService;
use frontend\services\login\abstracts\LoginAbstract;
use frontend\services\login\interfaces\LoginPhoneInterface;
use Yii;

class PhoneCodeLoginPhoneService extends LoginAbstract implements LoginPhoneInterface
{
    /**
     * 手机验证码登录
     *
     * @author   xudt
     * @dateTime 2019/11/16 15:03
     *
     * @param array $data
     *
     * @return array
     */
    public function login($data = [])
    {
        //验证手机号、验证码是否正确
        $verifyRes = $this->VerfiyPhoneCodeLogin($data['phone'], $data['verify_code']);
        if (!$verifyRes['result']) {
            return ToolsHelper::funcReturn("短信验证码验证失败");
        }

        //判断手机号是否已注册过
        $userService = new UserService();
        $userRes = $userService->getUserInfoByPhone($data['phone']);

        if (!$userRes['result']) { //注册
            $registerRes = $this->userRegisterByPhone($data);
            if (!$registerRes['result']) {
                return ToolsHelper::funcReturn("注册失败");
            }
            $data = $registerRes['data'];

            $data['awardInfo'] = "恭喜您完成手机注册，奖励" . $data['coin_num'] . "金币已到账";

            //发送系统消息
            $messageService = new MessageService();
            $messageService->add($data['uid'], MessageService::SYSTEM_WELCOME);
        } else { //登录
            $loginRes = $this->userLoginByPhone($userRes['data']);
            if (!$loginRes['result']) {
                return ToolsHelper::funcReturn($loginRes['message']);
            }
            $data = $loginRes['data'];
        }

        //清除短信验证码的redis值
        $this->clearPhoneCodeRedis($data['phone']);

        return ToolsHelper::funcReturn("登录成功", true, $data);
    }
}