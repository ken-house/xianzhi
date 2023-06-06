<?php
/**
 * 公众号服务
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/18 09:52
 */

namespace frontend\modules\api\controllers;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\services\OfficialAccountService;
use common\services\UnionService;
use Yii;

class OfficialAccountController extends BaseController
{
    const TOKEN = "xiaozha666888";
    const AppID = "wx2c2c44762e2fa049";
    const AppSecret = "d3edf1ee7f8b8e040a229b9621dca2df";
    const EncodingAESKey = "0nn8EjRPFHG8jGQc54PBmMAKcWKs8k2VAIXz9SvFWAe";

    /**
     * 验证token，绑定服务器配置使用
     *
     *
     * @author     xudt
     * @date-time  2021/8/18 11:25
     */
/*    public function actionIndex()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = SELF::TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        Yii::Info(['signature' => $signature, 'timestamp' => $timestamp, 'nonce' => $nonce, 'tmpStr' => $tmpStr, 'echoStr' => $echoStr], "signError");

        if ($tmpStr == $signature) {
            echo $echoStr;
            exit;
        } else {
            return false;
        }
    }*/

    public function actionIndex()
    {
        $postStr = file_get_contents("php://input");
        //extract post data
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            switch ($RX_TYPE) {
                case "text":
                    $resultStr = $this->handleText($postObj);
                    break;
                case "event":
                    $resultStr = $this->handleEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
            echo $resultStr;
        } else {
            echo "";
            exit;
        }
    }

    /**
     * 文本处理
     *
     * @param $postObj
     *
     * @author     xudt
     * @date-time  2021/8/19 16:51
     */
    private function handleText($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
//        $keyword = trim($postObj->Content);
        $time = time();
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
        $msgType = "text";
        $contentStr = "感谢您的留言，我们会尽快回复您，您也可以私信微信客服，祝您生活愉快！";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        echo $resultStr;
    }

    /**
     * 事件处理
     *
     * @param $postObj
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/8/19 16:51
     */
    private function handleEvent($postObj)
    {
        $unionService = new UnionService();
        $openid = $postObj->FromUserName;
        $contentStr = "";
        switch ($postObj->Event) {
            case "subscribe":
                $contentStr = "感谢您的关注，更多精彩请点击下方菜单进入小程序。";
                break;
            case "CLICK":
                if ($postObj->EventKey == "get_secret_key") {
                    $contentStr = $unionService->saveUnionRecord($openid);
                }
                break;
            default :
                $contentStr = "";
                break;
        }
        return $this->responseText($postObj, $contentStr);
    }

    /**
     * 回复文本信息
     *
     * @param     $object
     * @param     $content
     * @param int $flag
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/8/19 16:52
     */
    private function responseText($object, $content, $flag = 0)
    {
        if(empty($content)){
            return "";
        }
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        return sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
    }


    /**
     * 创建公众号菜单
     *
     *
     * @author     xudt
     * @date-time  2021/8/18 16:15
     */
    public function actionMenu()
    {
        die;
        $officialAccountService = new OfficialAccountService();
        $data['button'] = [
            [
                'name' => '关联小程序',
                'sub_button' => [
                    [
                        "type" => "click",
                        "name" => "获取动态码",
                        "key" => "get_secret_key",
                    ],
                    [
                        "type" => "miniprogram",
                        "name" => "立即关联",
                        "url" => "http://mp.weixin.qq.com",
                        "appid" => Yii::$app->params['weChat']['appid'],
                        "pagepath" => "pages/official_account/official_account"
                    ],
                ],
            ],
            [
                "type" => "miniprogram",
                "name" => "小程序",
                "url" => "http://mp.weixin.qq.com",
                "appid" => Yii::$app->params['weChat']['appid'],
                "pagepath" => "pages/index/index"
            ],
        ];

        $res = $officialAccountService->createMenu($data);
        if ($res) {
            return ToolsHelper::funcReturn("创建菜单成功", true);
        }
        return ToolsHelper::funcReturn("创建菜单失败");
    }

}