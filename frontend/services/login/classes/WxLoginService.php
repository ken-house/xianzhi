<?php
/**
 * 微信登录
 *
 * @author xudt
 * @date   : 2019/11/2 10:21
 */

namespace frontend\services\login\classes;

use common\services\MessageService;
use console\services\jobs\MessageJob;
use Yii;
use common\helpers\ToolsHelper;
use common\services\UserService;
use frontend\services\login\abstracts\LoginAbstract;
use frontend\services\login\interfaces\LoginPhoneInterface;

class WxLoginService extends LoginAbstract implements LoginPhoneInterface
{
    //微信小程序获取openid请求地址
    const  AUTHORIZATION_URL = "https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=APPSECRET&js_code=JSCODE&grant_type=authorization_code";

    /**
     * 微信登录
     *
     * @param array $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/17 12:25
     *
     */
    public function login($data = [])
    {
        $wxUserInfo = $data;

        //判断是否注册
        $userService = new UserService();
        $userRes = $userService->getUserInfoByWxOpenid($wxUserInfo['openId']);

        if (!$userRes['result']) { //注册
            $registerRes = $this->userRegisterByWechat($wxUserInfo, $data['invite_code']);
            if (!$registerRes['result']) {
                return ToolsHelper::funcReturn("注册失败");
            }
            $data = $registerRes['data'];

            // 发送系统消息
            if (!empty($data['invite_uid'])) { // 绑定好友
                // 发送系统消息
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => [
                                [
                                    'userInfo' => $data,
                                    'messageType' => MessageService::SYSTEM_INVITE_FRIEND_MESSAGE
                                ]
                            ]
                        ]
                    )
                );
            }
        } else { //登录
            $loginRes = $this->userLoginByWechat($userRes['data']);
            if (!$loginRes['result']) {
                return ToolsHelper::funcReturn($loginRes['message']);
            }
            $data = $loginRes['data'];
        }

        return ToolsHelper::funcReturn("登录成功", true, $data);
    }


    /**
     * 小程序获取微信openid
     *
     * @param string $code
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/5/12 14:02
     *
     */
    public function getWxOpenidByCode($code = "")
    {
        if (empty($code)) {
            return [];
        }
        $weChatParam = Yii::$app->params['weChat'];
        //发送wx请求认证
        $url = str_replace("APPID", $weChatParam['appid'], self::AUTHORIZATION_URL);
        $url = str_replace("APPSECRET", $weChatParam['secret'], $url);
        $url = str_replace("JSCODE", $code, $url);
        return ToolsHelper::sendRequest($url);
    }
}