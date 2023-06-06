<?php
/**
 * 发送短信
 *
 * @author xudt
 * @date   : 2019/12/21 13:21
 */
namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\SendPhoneCodeService;

use Yii;

class SmsController extends BaseController
{
    /**
     * 发送验证码
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/12/21 13:24
     */
    public function actionSendCode()
    {
        $userInfo = Yii::$app->params['userRedis'];
        if (!empty($userInfo['phone'])) {
            return ToolsHelper::funcReturn("您已绑定手机号，如需换绑请联系客服");
        }
        $phone = Yii::$app->request->post('phone');
        $type = Yii::$app->request->post('type', 2);  //1 登录注册  2 绑定手机  3 换绑验证手机号
        $sendPhoneCodeService = new SendPhoneCodeService();
        return $sendPhoneCodeService->sendCode($phone, $type);
    }
}