<?php
/**
 * @author xudt
 * @date   : 2019/11/25 15:38
 */
namespace frontend\components;

use common\helpers\ToolsHelper;
use Yii;
use yii\web\Response;

class BaseComponent
{
    /**
     * 是否为需要登录的接口
     * @author   xudt
     * @dateTime 2019/11/25 15:48
     * @param $action
     *
     * @return bool
     */
    public function isAuthUrl($action)
    {
        $module = $action->controller->module->id;
        $controller = $action->controller->id;
        $action = $action->id;
        $url = $module."/".$controller . "/" . $action;

        $noAuthUrlArr = Yii::$app->params['noAuthUrl'];
        if(in_array($url,$noAuthUrlArr)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 是否需要验证签名
     * @author   xudt
     * @dateTime 2019/11/25 19:59
     * @param $action
     *
     * @return bool
     */
    public function isSignUrl($action)
    {
        $module = $action->controller->module->id;
        $controller = $action->controller->id;
        $action = $action->id;
        $url = $module."/".$controller . "/" . $action;

        $noAuthUrlArr = Yii::$app->params['noSignUrl'];
        if(in_array($url,$noAuthUrlArr)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 是否需要登录
     * @author   xudt
     * @dateTime 2020/1/10 21:21
     * @param $action
     *
     * @return bool
     */
    public function isLoginUrl($action)
    {
        $module = $action->controller->module->id;
        $controller = $action->controller->id;
        $action = $action->id;
        $url = $module."/".$controller . "/" . $action;

        $noLoginUrlArr = Yii::$app->params['noLoginUrl'];
        if(in_array($url,$noLoginUrlArr)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 验证头信息的签名
     * @author   xudt
     * @dateTime 2019/11/25 20:24
     * @return bool
     */
    public function checkHeaderSign()
    {
        if(!Yii::$app->request->headers->has("sign")){
            return false;
        }
        $headerParams = [
            'AUTHORIZATION',
            'timestamp'
        ];
        $headerArr = [];
        foreach ($headerParams as $header){
            if(Yii::$app->request->headers->has($header)){
                $headerArr[$header] = Yii::$app->request->headers->get($header);
            }
        }
        ksort($headerArr);
        $headerStr = http_build_query($headerArr);

        $sign = Yii::$app->request->headers->get('sign');
        $secretHeader = Yii::$app->params['headerSignSecret'];

        if($sign!=md5($headerStr."&headerSignSecret=".$secretHeader)){
            return false;
        }
        return true;
    }

    /**
     * 验证参数的签名
     * @author   xudt
     * @dateTime 2019/11/25 20:24
     * @return bool
     */
    public function checkParamSign()
    {
        if (Yii::$app->request->isGet) {
            $params = Yii::$app->request->get();
        } elseif (Yii::$app->request->isPost) {
            $params = Yii::$app->request->post();
        } else {
            return false;
        }

        if (empty($params)) {
            return true;
        }

        if (!isset($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        unset($params['sign']);

        ksort($params);
        $paramsStr = "";
        foreach ($params as $key=>$value){
            $paramsStr .=$key."=".$value."&";
        }
        $paramsStr = trim($paramsStr,"&");

        $secretParam = Yii::$app->params['paramSignSecret'];

        if($sign!=md5($paramsStr."&signSecret=".$secretParam)){
            return false;
        }
        return true;
    }

    /**
     * 返回响应信息
     * @author   xudt
     * @dateTime 2019/11/25 20:58
     * @param string $message
     * @param bool   $result
     * @param array  $data
     *
     * @return array
     */
    public function errorResponse($message = '', $result = false, $data = [])
    {
        $response = Yii::$app->getResponse();
        $responseData = [
            'result' => $result,
            'message' => $message,
            'data' => $data,
        ];
        $responseData = ToolsHelper::intToString($responseData);
        $response->format = Response::FORMAT_JSON;
        $response->data = $responseData;
        return $responseData;
    }
}