<?php
/**
 * @author xudt
 * @date   : 2019/11/16 15:15
 */

namespace common\components;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\User;
use common\services\UserService;
use Firebase\JWT\JWT;
use yii\base\Component;
use Yii;

class JWTComponent extends Component
{
    //密钥存放的目录key
    public $certKey = '';
    //公钥、私钥存放路径
    private $rsaPrivateKeyPath = '/config/certs/jwt/{certKey}/rsa_private_key.pem';
    private $rsaPublicKeyPath = '/config/certs/jwt/{certKey}/rsa_public_key.pem';
    //token过期时间
    private $exp = 30 * 86400;

    //当前请求
    private $action;

    //需要刷新tokenid过期时间的请求地址
    private $refreshExpireUrl = [
        'api/user/userinfo',
    ];

    /**
     * jwt生成token
     *
     * @param $params
     *
     * @return string
     * @author   xudt
     * @dateTime 2019/11/16 21:25
     *
     */
    public function encodeToken($params)
    {
        $now = time();
        $domain = Yii::$app->params['domain'];
        $jwtConfig = Yii::$app->get('JWT');
        $this->certKey = $jwtConfig->certKey;
        $this->rsaPrivateKeyPath = Yii::getAlias('@common') . str_replace('{certKey}', $this->certKey, $this->rsaPrivateKeyPath);
        $key = openssl_get_privatekey(file_get_contents($this->rsaPrivateKeyPath));
        $tokenId = $this->getTokenId($params);
        if (empty($tokenId)) {
            return "";
        }
        $payload = [
            'iss' => $domain . $params['iss'],
            'jti' => $tokenId,
            'iat' => $now,
            'exp' => $now + $this->exp,
            'user' => [
                'uid' => intval($params['uid']),
                'nickname' => $params['nickname'],
            ],
        ];

        $header = [
            'typ' => 'JWT',
            'kid' => $this->certKey,
            'alg' => 'RS256',
            'crit' => ['iss', 'jti', 'iat', 'exp'],
        ];

        //过期时间设置为0 - JWT组件自己的过期时间
        JWT::$leeway = 0;
        $token = JWT::encode($payload, $key, 'RS256', $this->certKey, $header);
        return $token;
    }

    /**
     * 生成唯一值jti，保存在redis集合中，由于jwt自身的过期有bug,使用redis过期时间代替，jwt自身过期设置为3年
     *
     * @param $params
     *
     * @return string
     * @author   xudt
     * @dateTime 2019/11/16 21:26
     *
     */
    private function getTokenId($params)
    {
        $expireTime = time() + $this->exp;
        $redisKey = RedisHelper::RK("authTokenId", $params['uid']);
        /** @var \redisCluster $redisBaseCluster */
        $redisCluster = Yii::$app->get("redisBase")->getRedisCluster();

        //生成jti
        $jwtTokenSecret = Yii::$app->params['jwtTokenSecret'];
        $jti = md5($params['uid'] . $params['nickname'] . time() . $jwtTokenSecret);

        if ($redisCluster->zAdd($redisKey, $expireTime, $jti)) {
            $redisCluster->expireAt($redisKey, $expireTime);
            return $jti;
        } else {
            return "";
        }
    }


    /**
     * jwt解密
     *
     * @param $token
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/16 21:53
     *
     */
    private function decodeToken($token)
    {
        $jwtConfig = Yii::$app->get('JWT');
        $this->certKey = $jwtConfig->certKey;
        $this->rsaPublicKeyPath = Yii::getAlias('@common') . str_replace('{certKey}', $this->certKey, $this->rsaPublicKeyPath);
        $certsKeys[$this->certKey] = openssl_get_publickey(file_get_contents($this->rsaPublicKeyPath));

        //过期时间延长3年 - JWT组件自己的过期时间
        JWT::$leeway = 86400 * 365 * 3;
        try {
            $payload = JWT::decode($token, $certsKeys, ['RS256']);
            if (isset($payload->jti)) {
                $data = json_decode(json_encode($payload), true);
                return ToolsHelper::funcReturn("jwt解密成功", true, $data);
            }
        } catch (\Exception $e) {
        }
        return ToolsHelper::funcReturn("jwt解密失败");
    }


    /**
     * 检测token，并解出用户信息
     *
     * @param $action
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/26 12:13
     */
    public function checkToken($action)
    {
        $expireTime = time() + $this->exp;
        $token = Yii::$app->request->headers->get('AUTHORIZATION');
        $decodeTokenResult = $this->decodeToken($token);

        //设置全局变量
        Yii::$app->params['userRedis'] = [];

        if ($decodeTokenResult['result']) {
            $payload = $decodeTokenResult['data'];
            $tokenId = $payload['jti'];
            //检测tokenId是否过期
            $redisKey = RedisHelper::RK("authTokenId", $payload['user']['uid']);
            /** @var \redisCluster $redisCluster */
            $redisCluster = Yii::$app->get("redisBase")->getRedisCluster();
            $ttl = $redisCluster->ttl($redisKey); //rediskey过期时间
            if ($ttl > 0) { //过期时间
                $tokenIdExpire = $redisCluster->zScore($redisKey, $tokenId);
                if ($tokenIdExpire > time()) { //tokenid有效
                    $redisCluster->zRemRangeByScore($redisKey, 0, $tokenIdExpire - 3); //清理老的tokenid
                    //判断是否需要刷新过期时间
                    if ($this->isRefreshExpireUrl($action)) {
                        //延长过期时间
                        $redisCluster->zIncrBy($redisKey, $expireTime, $tokenId);
                        //延长key过期时间
                        $redisCluster->expireAt($redisKey, $expireTime);
                    }
                    $userService = new UserService();
                    Yii::$app->params['userRedis'] = $userService->getUserAllDataFromRedisMysql($payload['user']['uid']);
                    return ToolsHelper::funcReturn("token正确，获取用户信息成功", true, $payload['user']);
                } elseif ($tokenIdExpire) {
                    return ToolsHelper::funcReturn("tokenid失效");
                } else {
                    return ToolsHelper::funcReturn("账号已在另一个设备登录");
                }
            } elseif ($ttl == -2) { //rediskey不存在
                return ToolsHelper::funcReturn("账号异常，请重新登录");
            } else { //rediskey存在，但没有过期时间
                $redisCluster->del($redisKey);
                return ToolsHelper::funcReturn("账号异常，请重新登录");
            }
        } else {
            return $decodeTokenResult;
        }
    }

    /**
     * 是否为需要刷新的请求地址
     *
     * @param $action
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/26 12:15
     */
    private function isRefreshExpireUrl($action)
    {
        if(empty($action)){
            return false;
        }
        $module = $action->controller->module->id;
        $controller = $action->controller->id;
        $action_name = $action->id;
        $str = $module . "/" . $controller . "/" . $action_name;

        if (in_array($str, $this->refreshExpireUrl)) {
            return true;
        }
        return false;
    }
}