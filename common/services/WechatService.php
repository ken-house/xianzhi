<?php
/**
 * @author xudt
 * @date   : 2019/11/12 11:22
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use Yii;
use \CURLFile;

class WechatService
{
    // 获取access_token接口
    const GET_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET';
    // 消息订阅推送接口
    const PUSH_TEMP_MSG_URL = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=ACCESS_TOKEN';
    // 获取消息订阅模板列表接口
    const GET_TEMPLATE_URL = 'https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token=ACCESS_TOKEN';
    // 内容审核接口
    const MSG_SEC_CHECK_URL = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=ACCESS_TOKEN';
    // 图片审核接口
    const IMG_SEC_CHECK_URL = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=ACCESS_TOKEN';
    // access_token过期时间为两小时，提前200秒结束
    const ACCESSTOKENLIFETIME = 7000;

    /**
     * 获取微信小程序登录的用户信息
     *
     * @param $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/5/17 10:31
     *
     */
    public function getWxUserInfo($data)
    {
        $appId = Yii::$app->params['weChat']['appid'];
        $aesKey = base64_decode($data['session_key']);
        $aesIV = base64_decode($data['iv']);
        $aesCipher = base64_decode($data['encryptedData']);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $data = json_decode($result, true);
        if (empty($data)) {
            return ToolsHelper::funcReturn("获取用户信息失败");
        }
        if ($data['watermark']['appid'] != $appId) {
            return ToolsHelper::funcReturn("获取用户信息失败");
        }
        return ToolsHelper::funcReturn("获取用户信息成功", true, $data);
    }

    /**
     * 小程序获取access_token
     *
     * @return array
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/10/10 21:02
     */
    public function getAccessToken()
    {
        /** @var \rediscluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get("redisBase")->getRedisCluster();
        $redisKey = RedisHelper::RK("wechatAccessToken");
        $accessToken = $redisBaseCluster->get($redisKey);
        if (empty($accessToken)) {
            $weChatParam = Yii::$app->params['weChat'];
            $url = str_replace("APPID", $weChatParam['appid'], self::GET_ACCESS_TOKEN_URL);
            $url = str_replace("APPSECRET", $weChatParam['secret'], $url);
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
     * 微信内容审核
     *
     * @param $content
     *
     * @return bool
     *
     * @author    xudt
     * @dateTime  2020/11/17 13:49
     */
    public function msgSecCheck($content)
    {
        $accessToken = $this->getAccessToken();
        $url = str_replace("ACCESS_TOKEN", $accessToken, self::MSG_SEC_CHECK_URL);
        $data = [
            'content' => $content,
        ];
        $responseData = ToolsHelper::postRequestOrigin($url, $data);
        $dataRes = json_decode($responseData, true);
        if (isset($dataRes['errcode']) && $dataRes['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 微信图片审核(图片大小不得超过1M)
     *
     * @param $tmpName
     *
     * @return bool
     *
     * @author    xudt
     * @dateTime  2020/11/17 13:49
     */
    public function imgSecCheck($tmpName)
    {
        $accessToken = $this->getAccessToken();
        $url = str_replace("ACCESS_TOKEN", $accessToken, self::IMG_SEC_CHECK_URL);
        $data = [
            'media' => new CURLFile($tmpName),
        ];
        $responseData = ToolsHelper::postRequestOrigin($url, $data, false);
        $dataRes = json_decode($responseData, true);
        if (isset($dataRes['errcode']) && $dataRes['errcode'] == 0) {
            return true;
        }
        return false;
    }
}