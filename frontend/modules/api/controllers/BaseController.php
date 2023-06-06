<?php
/**
 * @author xudt
 * @date   : 2019/11/2 11:01
 */

namespace frontend\modules\api\controllers;

use common\components\JWTComponent;
use common\helpers\ToolsHelper;
use frontend\components\BaseComponent;
use Yii;
use yii\base\Controller;

class BaseController extends Controller
{
    public $response;

    /**
     * @var BaseComponent 基础组件对象
     */
    public $baseComponent;

    public function init()
    {
        $this->baseComponent = new BaseComponent();
    }

    /**
     * 控制器前置方法
     *
     * @param \yii\base\Action $action
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/25 15:17
     *
     */
    public function beforeAction($action)
    {
        $this->response = Yii::$app->getResponse();
        if (parent::beforeAction($action)) {
            //判断请求地址是否需要验证签名
            $isSignUrl = $this->baseComponent->isSignUrl($action);
            if ($isSignUrl && !YII_ENV_DEV) {
                //验证头信息签名是否正确
                $checkHeaderSignRes = $this->baseComponent->checkHeaderSign();
                $checkParamSignRes = $this->baseComponent->checkParamSign();
                if (!$checkHeaderSignRes || !$checkParamSignRes) {
                    // 记录签名错误日志
                    $header = Yii::$app->request->headers;
                    $paramsData = Yii::$app->request->get();
                    if (Yii::$app->request->isPost) {
                        $paramsData = Yii::$app->request->post();
                    }
                    Yii::info(['requestUrl' => Yii::$app->request->url, 'headerData' => $header, 'paramsData' => $paramsData], 'signError');

                    $this->baseComponent->errorResponse("请重新进入小程序");
                    return false;
                }
            }

            //判断请求地址是否需要登录
            $isAuthUrl = $this->baseComponent->isAuthUrl($action);
            if ($isAuthUrl) {
                if (YII_ENV_DEV) {
                    Yii::$app->params['userRedis'] = [
                        'uid' => 100000,
                        'nickname' => '枫🌹',
                        'gender' => 1,
                        'avatar' => 'https://thirdwx.qlogo.cn/mmopen/vi_32/UUUkicPVNK4N16SYQQr7GECCXSEu96rAK7jVOrwDTIeaQjC5cdQfFjPIzJS8UrKxCbM0aBiax9IaQibY4n1fFf4EQ/132',
                        'wx_openid' => 'ooKuK5N6NMy3RzFfQqsT0lzzCR9Q',
                        'wx' => 'S1136728155',
                        'invite_code' => '',
                        'last_sign_day' => 0,
                        'continue_sign_day' => 0,
                        'reward_point' => 0,
                        'created_at' => 1,
                    ];
                    return true;
                }
                // 校验jwt,并从jwt中取出用户uid
                $jwt = new JWTComponent();
                $checkTokenRes = $jwt->checkToken($action);
                if (!$checkTokenRes['result']) { //解jwt失败
                    $isLoginUrl = $this->baseComponent->isLoginUrl($action);
                    if ($isLoginUrl) {
                        $this->baseComponent->errorResponse("登录失效，请重新登录", false, ['login' => 'fail']);
                        return false;
                    } else { //不要求登录
                        Yii::$app->params['userRedis'] = Yii::$app->params['noLoginUser'];
                        return true;
                    }
                }
            }
        } else {
            return false;
        }
        return true;
    }


    /**
     * 控制器后置方法
     *
     * @param \yii\base\Action $action
     * @param mixed            $result
     *
     * @return array|mixed|string
     * @author   xudt
     * @dateTime 2019/11/25 15:17
     *
     */
    public function afterAction($action, $result)
    {
        if (!is_array($result)) {
            return $result;
        }
        $this->response->format = "json";
        $result = parent::afterAction($action, $result);
        return ToolsHelper::intToString($result);
    }
}