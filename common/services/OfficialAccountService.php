<?php
/**
 * 公众号服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/18 15:51
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use Yii;

class OfficialAccountService
{
    // 获取access_token接口
    const GET_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET";
    // 发送模板消息接口
    const SEND_TMP_MSG_URL = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=ACCESS_TOKEN";
    // 创建自定义菜单
    const CREATE_MENU_URL = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN";
    // 获取用户信息
    const GET_USER_INFO_URL = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN";
    // access_token过期时间为两小时，提前200秒结束
    const ACCESSTOKENLIFETIME = 7000;

    /**
     * 获取access_token
     *
     * @return false|mixed|string
     *
     * @author     xudt
     * @date-time  2021/8/18 15:59
     */
    public function getAccessToken()
    {
        /** @var \rediscluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get("redisBase")->getRedisCluster();
        $redisKey = RedisHelper::RK("officialAccountAccessToken");
        $accessToken = $redisBaseCluster->get($redisKey);
        if (empty($accessToken) && !YII_ENV_DEV) {
            $officialAccountParam = Yii::$app->params['officialAccount'];
            $url = str_replace("APPID", $officialAccountParam['appid'], self::GET_ACCESS_TOKEN_URL);
            $url = str_replace("APPSECRET", $officialAccountParam['secret'], $url);
            $responseData = ToolsHelper::sendRequest($url);
            //两小时过期，设置redis过期时间为7000
            $accessToken = isset($responseData['access_token']) ? $responseData['access_token'] : "";
            if (!empty($accessToken)) {
                $redisBaseCluster->set($redisKey, $accessToken, self::ACCESSTOKENLIFETIME);
            }
        }
        return $accessToken;
    }

    /**
     * 查找用户信息
     *
     * @param $openId
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/8/19 15:57
     */
    public function getUserInfo($openId)
    {
        $accessToken = $this->getAccessToken();
        $url = str_replace("ACCESS_TOKEN", $accessToken, self::GET_USER_INFO_URL);
        $url = str_replace("OPENID", $openId, $url);
        $responseData = ToolsHelper::sendRequest($url);
        if(isset($responseData['errcode'])){
            return [];
        }
        return $responseData;
    }


    /**
     * 创建自定义菜单
     *
     * @param $data
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/8/18 16:21
     */
    public function createMenu($data)
    {
        $accessToken = $this->getAccessToken();
        $url = str_replace("ACCESS_TOKEN", $accessToken, self::CREATE_MENU_URL);
        $responseData = ToolsHelper::postRequestOrigin($url, $data);
        $dataRes = json_decode($responseData, true);
        if (isset($dataRes['errcode']) && $dataRes['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 发送模板消息
     *
     * @param        $openId
     * @param        $templateId
     * @param string $redirectUrl
     * @param array  $miniprogram
     * @param array  $data
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/8/18 16:13
     */
    public function sendTmpMsg($openId, $templateId, $redirectUrl = "", $miniprogram = [], $data = [])
    {
        $accessToken = $this->getAccessToken();
        $url = str_replace("ACCESS_TOKEN", $accessToken, self::SEND_TMP_MSG_URL);
        $content = [
            'touser' => $openId,
            'template_id' => $templateId,
            'url' => $redirectUrl,
            'miniprogram' => $miniprogram,
            'data' => $data
        ];
        $responseData = ToolsHelper::sendRequest($url, "POST", $content);
        if ($responseData['errcode'] == 0) {
            return true;
        }
        return false;
    }

}