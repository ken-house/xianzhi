<?php

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\UserService;
use frontend\services\login\classes\WxLoginService;
use Yii;

/**
 * 登录相关接口
 */
class LoginController extends BaseController
{
    /**
     * 用户登录（默认微信登录）
     *
     * @author   xudt
     * @dateTime 2020/3/25 11:23
     * @return array
     */
    public function actionLogin()
    {
        if (!Yii::$app->request->isPost) {
            echo "非法操作";
            die;
        }
        $postData = Yii::$app->request->post();
        // 若填写邀请码（POST请求）或通过邀请链接注册（GET请求）,需要建立好友关系
        if (empty($postData['invite_code'])) {
            $postData['invite_code'] = Yii::$app->request->get('invite_code', '');
        }
        $loginService = new WxLoginService();
        return $loginService->login($postData);
    }

    /**
     * 退出登录
     *
     * @author   xudt
     * @dateTime 2020/3/26 19:32
     * @return array
     */
    public function actionLogout()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = intval($userInfo['uid']);

        $userService = new UserService();
        $userService->deleteUserAuthTokenRedis($uid);

        return ToolsHelper::funcReturn("退出登录", true);
    }


    /**
     * 小程序根据code换取openid
     *
     * @author   xudt
     * @dateTime 2020/5/12 14:04
     * @return array
     */
    public function actionGetWxOpenid()
    {
        $code = Yii::$app->request->get("code");
        $wxLoginService = new WxLoginService();
        $data = $wxLoginService->getWxOpenidByCode($code);
        if (empty($data)) {
            return ToolsHelper::funcReturn("请求失败");
        }
        return ToolsHelper::funcReturn("请求成功", true, $data);
    }
}
