<?php
/**
 * @author xudt
 * @date   : 2019/11/11 19:03
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Address;
use common\models\Report;
use common\models\SearchKeyword;
use common\models\User;
use common\models\UserData;
use common\models\UserDestory;
use common\models\UserWechatInfo;
use Yii;
use yii\db\Exception;

class UserService
{
    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;

    const USERREDISLIFTTIME = 15 * 86400;
    const RANKUSERDATALIFETIME = 86400 * 3;

    public function __construct()
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
    }

    /**
     * 设置密码
     *
     * @param        $uid
     * @param string $password
     *
     * @return array
     * @throws \yii\base\Exception
     * @author   xudt
     * @dateTime 2019/11/11 19:18
     *
     */
    public function passwordSetting($uid, $password = '')
    {
        $hashPassword = Yii::$app->getSecurity()->generatePasswordHash($password);
        $data['password'] = $hashPassword;
        $data['updated_at'] = time();
        $res = User::updateAll($data, ['id' => $uid]);
        if ($res) {
            return ToolsHelper::funcReturn("设置密码成功", true);
        } else {
            return ToolsHelper::funcReturn("设置密码失败");
        }
    }

    /**
     * 验证手机号密码
     *
     * @param $phone
     * @param $password
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/11 19:21
     *
     */
    public function verifyPassword($phone, $password)
    {
        $userInfo = User::find()->where(['phone' => $phone])->asArray()->one();
        if (empty($userInfo)) {
            return ToolsHelper::funcReturn("手机号未注册，请通过手机号验证登录");
        }
        if (empty($userInfo['password'])) {
            return ToolsHelper::funcReturn("你还未设置密码，请通过手机号验证登录后设置密码");
        }
        if (Yii::$app->getSecurity()->validatePassword($password, $userInfo['password'])) {
            unset($userInfo['password']);
            return ToolsHelper::funcReturn("密码验证成功", true, $userInfo);
        } else {
            return ToolsHelper::funcReturn("密码验证失败");
        }
    }

    /**
     * 通过手机号判断用户是否已存在
     *
     * @param $phone
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/14 19:57
     *
     */
    public function getUserInfoByPhone($phone)
    {
        $userInfo = User::find()->where(['phone' => $phone])->asArray()->one();
        if (!empty($userInfo)) {
            $userInfo['password'] = !empty($userInfo['password']) ? md5($userInfo['password']) : '';
            return ToolsHelper::funcReturn("用户存在", true, $userInfo);
        } else {
            return ToolsHelper::funcReturn("用户不存在");
        }
    }

    /**
     * 用户注册写入数据
     *
     * @param      $phone
     * @param bool $isDestoryedUser
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/3/27 14:17
     *
     */
    public function userRegByPhone($phone, $isDestoryedUser = false)
    {
        $now = time();
        if ($isDestoryedUser) { //注销过用户不给奖励
            $coinNum = 0;
        } else {
            $coinTypeArr = Yii::$app->params['coinType'];
            $coinNum = $coinTypeArr[10000]['coin_num'];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            //写入user表
            $userModel = new User();
            $userModel->nickname = ToolsHelper::makeNickname();
            $userModel->phone = $phone;
            $userModel->avatar = "";
            $userModel->cover = "";
            $userModel->birthday = 0;
            $userModel->created_at = $now;
            $userModel->updated_at = $now;
            $userResult = $userModel->save();
            $userId = $userModel->id;
            if ($userResult) {
                //写入user表
                $userDataModel = new UserData();
                $userDataModel->uid = $userId;
                $userDataModel->invite_code = ToolsHelper::createInviteCode($userId);
                $userDataModel->latest_login_at = $now;
                $userDataModel->latest_login_ip = sprintf("%u", ip2long(Yii::$app->request->userIP)); //用户最近登录ip
                $userDataModel->login_num = 1;
                $userDataModel->history_coin_num = $coinNum;
                $userDataModel->coin_num = $coinNum;
                $userDataModel->created_at = $now;
                $userDataModel->updated_at = $now;
                $userDataResult = $userDataModel->save();
                if ($userDataResult) {
                    $transaction->commit();
                    $userRedisData = $this->makeUserRedisData($userModel, $userDataModel);
                    return ToolsHelper::funcReturn("注册数据写入成功", true, $userRedisData);
                } else {
                    return ToolsHelper::funcReturn("注册数据写入失败");
                }
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new Exception("注册失败,错误信息：" . $e->getMessage());
            return ToolsHelper::funcReturn("注册数据写入异常");
        }
    }

    /**
     * 注册时将用户数据写入组装完整
     *
     * @param $userModel
     * @param $userDataModel
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/12/5 17:42
     *
     */
    private function makeUserRedisData($userModel, $userDataModel)
    {
        $userRedisData = array_merge($userModel->attributes, $userDataModel->attributes);
        return $userRedisData;
    }


    /**
     * 登录
     *
     * @param $userInfo
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/16 14:57
     *
     */
    public function userLogin($userInfo)
    {
        $now = time();
        $userDataModel = UserData::find()->where(['uid' => $userInfo['id']])->one();
        $userDataModel->latest_login_at = $now;
        $userDataModel->latest_login_ip = sprintf("%u", ip2long(Yii::$app->request->userIP)); //用户最近登录ip
        $userDataModel->login_num += 1;
        $userDataModel->active_at = $now;
        $userDataModel->updated_at = $now;
        $res = $userDataModel->save();
        if ($res) {
            $userInfo = array_merge($userInfo, $userDataModel->attributes);
            return ToolsHelper::funcReturn("登录成功", true, $userInfo);
        } else {
            return ToolsHelper::funcReturn("登录失败");
        }
    }

    /**
     * 通过微信openid查询用户是否存在
     *
     * @param $wxOpenid
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/17 11:46
     *
     */
    public function getUserInfoByWxOpenid($wxOpenid)
    {
        $userInfo = User::find()->where(['wx_openid' => $wxOpenid])->asArray()->one();
        if (!empty($userInfo)) {
            return ToolsHelper::funcReturn("用户存在", true, $userInfo);
        }
        return ToolsHelper::funcReturn("用户不存在");
    }

    /**
     * 微信注册
     *
     * @param      $wxUserInfo
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/5/17 11:10
     *
     */
    public function UserRegByWechat($wxUserInfo)
    {
        $now = time();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            //写入user表
            $userModel = new User();
            $userModel->loadDefaultValues();
            $userModel->nickname = $wxUserInfo['nickName'];
            $userModel->avatar = $wxUserInfo['avatarUrl'];
            $userModel->wx_openid = $wxUserInfo['openId'];
            $userModel->wx = '';
            $userModel->gender = $wxUserInfo['gender'];
            $userModel->country = $wxUserInfo['country'];
            $userModel->province = $wxUserInfo['province'];
            $userModel->city = $wxUserInfo['city'];
            $userModel->created_at = $now;
            $userModel->updated_at = $now;
            $userResult = $userModel->save();
            $userId = $userModel->id;
            if ($userResult) {
                //写入user表
                $userDataModel = new UserData();
                $userDataModel->loadDefaultValues();
                $userDataModel->uid = $userId;
                $userDataModel->invite_code = ToolsHelper::createInviteCode($userId);
                $userDataModel->latest_login_at = $now;
                $userDataModel->latest_login_ip = sprintf("%u", ip2long(Yii::$app->request->userIP)); //用户最近登录ip
                $userDataModel->login_num = 1;
                $userDataModel->active_at = $now; // 活跃时间
                $userDataModel->reward_point = 0; //积分
                $userDataModel->created_at = $now;
                $userDataModel->updated_at = $now;
                $userDataResult = $userDataModel->save();

                //写入user_wechat_info表
                $userWechatInfoModel = new UserWechatInfo();
                $userWechatInfoModel->openid = $wxUserInfo['openId'];
                $userWechatInfoModel->unionid = isset($wxUserInfo['unionId']) ? $wxUserInfo['unionId'] : "";
                $userWechatInfoModel->gender = $wxUserInfo['gender'];
                $userWechatInfoModel->nickname = $wxUserInfo['nickName'];
                $userWechatInfoModel->avatar_url = $wxUserInfo['avatarUrl'];
                $userWechatInfoModel->province = $wxUserInfo['province'];
                $userWechatInfoModel->city = $wxUserInfo['city'];
                $userWechatInfoModel->country = $wxUserInfo['country'];
                $userWechatInfoModel->created_at = $now;
                $userWechatInfoModel->updated_at = $now;
                $userWechatInfoResult = $userWechatInfoModel->save();
                if ($userDataResult && $userWechatInfoResult) {
                    $transaction->commit();
                    $userRedisData = $this->makeUserRedisData($userModel, $userDataModel);
                    return ToolsHelper::funcReturn("注册数据写入成功", true, $userRedisData);
                } else {
                    $transaction->rollBack();
                    return ToolsHelper::funcReturn("注册数据写入失败");
                }
            } else {
                $transaction->rollBack();
                return ToolsHelper::funcReturn("注册数据写入失败");
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \Exception($e->getMessage());
            return ToolsHelper::funcReturn("注册数据写入异常");
        }
    }


    /**
     * 将用户信息、数据写入到redis中
     *
     * @param array $userInfo
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/12/5 16:30
     *
     */
    public function updateUserInfoToRedis(&$userInfo = [])
    {
        $userinfoRes = $this->updateStructureDataToUserInfoRedis($userInfo['uid'], $userInfo);
        if ($userinfoRes) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户基本信息
     *
     * @param $uid
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/4/7 10:13
     *
     */
    public function getUserInfoFromRedis($uid)
    {
        $userInfoKey = RedisHelper::RK('userInfo', $uid);
        $userInfo = $this->redisBaseCluster->hGetAll($userInfoKey);
        if (!empty($userInfo)) {
            $this->redisBaseCluster->expire($userInfoKey, self::USERREDISLIFTTIME);
        }
        return $userInfo;
    }

    /**
     * 获取单个用户信息
     *
     * @param $uid
     * @param $structName
     *
     * @return false|string
     *
     * @author     xudt
     * @date-time  2021/3/9 09:16
     */
    public function getUserStructFromRedis($uid, $structName)
    {
        $userInfoKey = RedisHelper::RK('userInfo', $uid);
        return $this->redisBaseCluster->hGet($userInfoKey, $structName);
    }

    /**
     * 从redis中获取用户基础数据
     *
     * @param $uid
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/2/28 15:46
     *
     */
    public function getUserDataFromRedis($uid)
    {
        //读取userData
        $userDataKey = RedisHelper::RK('userData', $uid);
        $userData = $this->redisBaseCluster->hGetAll($userDataKey);
        //设置过期时间
        if (!empty($userData)) {
            if (count($userData) != count(Yii::$app->params['redis.config']['userData']['structure'])) { //查询数据库，补全字段
                $userDataMysql = UserData::find()->where(['uid' => $uid])->asArray()->one();
                $userData = $userData + $userDataMysql;

                $structure = RedisHelper::RS('userData');
                $userDataRedisData = [];
                foreach ($structure as $key => $value) {
                    if (isset($userData[$value])) {
                        $userDataRedisData[$value] = $userData[$value];
                    }
                }

                $this->redisBaseCluster->hMSet($userDataKey, $userDataRedisData);
            }
            $this->redisBaseCluster->expire($userDataKey, self::USERREDISLIFTTIME);
        }
        return $userData;
    }


    /**
     * 优先从redis读取用户信息，若没有读取到，再从数据库中读取并写入redis
     *
     * @param $uid
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/12/4 16:22
     *
     */
    public function getUserAllDataFromRedisMysql($uid)
    {
        $data = $this->getUserInfoFromRedis($uid);
        //若redis中读取不到用户信息，则从数据库中读取并写入到redis
        if (empty($data)) {
            $userInfoMysql = User::find()->where(['id' => $uid])->asArray()->one();
            $userDataMysql = UserData::find()->where(['uid' => $uid])->asArray()->one();
            if (!empty($userInfoMysql) && !empty($userDataMysql)) {
                $data = array_merge($userInfoMysql, $userDataMysql);
                self::updateUserInfoToRedis($data);
            }
        }
        return $data;
    }

    /**
     * 更新userinfo Redis
     *
     * @param         $uid
     * @param array   $userInfo
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/12/5 16:29
     *
     */
    public function updateStructureDataToUserInfoRedis($uid, $userInfo = [])
    {
        $userInfoKey = RedisHelper::RK('userInfo', $uid);
        $structure = RedisHelper::RS('userInfo');

        $userInfoRedisData = [];
        foreach ($structure as $key => $value) {
            if (isset($userInfo[$value])) {
                $userInfoRedisData[$value] = $userInfo[$value];
            }
        }
        $setRes = $this->redisBaseCluster->hMSet($userInfoKey, $userInfoRedisData);
        if ($setRes) {
            $this->redisBaseCluster->expire($userInfoKey, self::USERREDISLIFTTIME);
            return true;
        }
        return false;
    }


    /**
     * 设置密码
     *
     * @param $uid
     * @param $password
     *
     * @return bool
     * @throws \yii\base\Exception
     * @author   xudt
     * @dateTime 2020/2/3 14:47
     *
     */
    public function settingPassword($uid, $password)
    {
        $now = time();
        //设置密码
        $hashPassword = Yii::$app->getSecurity()->generatePasswordHash($password);
        $res = $this->saveUserinfo(
            $uid,
            [
                'password' => $hashPassword,
                'updated_at' => $now
            ]
        );
        return $res;
    }

    /**
     * 保存用户基本信息
     *
     * @param       $uid
     * @param array $userInfo
     *
     * @return int
     * @author   xudt
     * @dateTime 2020/3/26 15:42
     *
     */
    public function saveUserinfo($uid, $userInfo = [])
    {
        $res = User::updateAll($userInfo, ['id' => $uid]);
        if ($res) {
            $this->updateStructureDataToUserInfoRedis($uid, $userInfo);
        }
        return $res;
    }

    /**
     * 保存用户数据
     *
     * @param       $uid
     * @param array $userInfo
     *
     * @return int
     * @author   xudt
     * @dateTime 2020/3/26 15:42
     *
     */
    public function saveUserData($uid, $userInfo = [])
    {
        $res = UserData::updateAll($userInfo, ['uid' => $uid]);
        if ($res) {
            $this->updateStructureDataToUserInfoRedis($uid, $userInfo);
        }
        return $res;
    }

    /**
     * 删除用户登录的token redis
     *
     * @param $uid
     *
     * @return int
     * @author   xudt
     * @dateTime 2020/3/26 19:28
     *
     */
    public function deleteUserAuthTokenRedis($uid)
    {
        $redisKey = RedisHelper::RK("authTokenId", $uid);
        return $this->redisBaseCluster->del($redisKey);
    }


    /**
     * 我的地址列表
     *
     * @param $uid
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/3/11 13:57
     */
    public function getAddressList($uid)
    {
        return Address::find()->where(['uid' => $uid])->orderBy('updated_at desc')->asArray()->all();
    }

    /**
     * 获取
     *
     * @param $uid
     * @param $id
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/18 14:23
     */
    public function getAddressInfo($uid, $id)
    {
        return Address::find()->where(['uid' => $uid, 'id' => $id])->asArray()->one();
    }

    /**
     * 添加地址
     *
     * @param $uid
     * @param $postData
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/11 14:03
     */
    public function addAddress($uid, $postData)
    {
        $now = time();
        $addressModel = new Address();
        $addressModel->uid = $uid;
        $addressModel->name = $postData['name'];
        $addressModel->province = $postData['province'];
        $addressModel->city = $postData['city'];
        $addressModel->district = $postData['district'];
        $addressModel->address = $postData['address'];
        $addressModel->lat = $postData['lat'];
        $addressModel->lng = $postData['lng'];
        $addressModel->created_at = $now;
        $addressModel->updated_at = $now;
        if ($addressModel->save()) {
            return ToolsHelper::funcReturn("添加地址成功", true);
        }
        return ToolsHelper::funcReturn("添加地址失败");
    }

    /**
     * 修改地址
     *
     * @param $uid
     * @param $postData
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/11 14:07
     */
    public function editAddress($uid, $postData)
    {
        $now = time();
        $addressModel = Address::find()->where(['id' => $postData['id']])->one();
        if (empty($addressModel)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        if ($uid != $addressModel->uid) {
            return ToolsHelper::funcReturn("非法操作");
        }
        $addressModel->name = $postData['name'];
        $addressModel->province = $postData['province'];
        $addressModel->city = $postData['city'];
        $addressModel->district = $postData['district'];
        $addressModel->address = $postData['address'];
        $addressModel->lat = $postData['lat'];
        $addressModel->lng = $postData['lng'];
        $addressModel->updated_at = $now;
        if ($addressModel->save()) {
            return ToolsHelper::funcReturn("编辑地址成功", true);
        }
        return ToolsHelper::funcReturn("编辑地址失败");
    }

    /**
     * 删除地址
     *
     * @param $uid
     * @param $id
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/17 20:49
     */
    public function deleteAddress($uid, $id)
    {
        $res = Address::deleteAll(['uid' => $uid, 'id' => $id]);
        if ($res) {
            return ToolsHelper::funcReturn("删除地址成功", true);
        }
        return ToolsHelper::funcReturn("删除地址失败");
    }

    /**
     * 批量查询用户昵称
     *
     * @param $uidArr
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 11:37
     */
    public function getUserNickname($uidArr)
    {
        return User::find()->select(['nickname'])->where(['id' => $uidArr])->indexBy('id')->column();
    }

    /**
     * 批量查询用户昵称、头像
     *
     * @param $uidArr
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 11:37
     */
    public function getUserInfoByUidArr($uidArr)
    {
        return User::find()->select(['id', 'nickname', 'avatar', 'gender'])->where(['id' => $uidArr])->indexBy('id')->asArray()->all();
    }

    /**
     * 用户积分排行榜
     *
     * @param int $type
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/13 11:19
     */
    public function getUserPointRank($type = 0)
    {
        $date = date("Ymd");
        if ($type == 1) {
            $date = date("Ymd", strtotime("-1 day"));
        }

        // 积分排行榜数据
        $rankRedisKey = RedisHelper::RK('userPointRank', $date);
        $dataList = $this->redisBaseCluster->zRevRange($rankRedisKey, 0, 99, true);
        $uidArr = [];
        $rankList = [];
        if (!empty($dataList)) {
            foreach ($dataList as $uid => $point) {
                $uidArr[] = $uid;
            }
            $userArr = $this->getUserInfoByUidArr($uidArr);
            $rank = 1;
            foreach ($dataList as $uid => $point) {
                if (!isset($userArr[$uid])) {
                    continue;
                }
                $rankList[$uid] = [
                    'uid' => $uid,
                    'nickname' => $userArr[$uid]['nickname'],
                    'avatar' => ToolsHelper::getLocalImg($userArr[$uid]['avatar']),
                    'gender' => $userArr[$uid]['gender'],
                    'point' => $point,
                    'rank' => $rank
                ];
                $rank++;
            }
        }
        return $rankList;
    }

    /**
     * 记录用户想要的用户uid
     *
     * @param $uid
     * @param $productUid
     *
     * @author     xudt
     * @date-time  2021/5/26 22:32
     */
    public function recordUserWantUid($uid, $productUid)
    {
        $now = time();
        $date = date("Ymd", $now);
        $redisKey = RedisHelper::RK('userWantUid', $uid, $date);
        $this->redisBaseCluster->zAdd($redisKey, $now, $productUid);
    }

    /**
     * 用户想要的用户数
     *
     * @param $uid
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/5/26 22:38
     */
    public function denyUserWant($uid)
    {
        $now = time();
        $date = date("Ymd", $now);
        $redisKey = RedisHelper::RK('userWantUid', $uid, $date);
        $count = $this->redisBaseCluster->zCard($redisKey);
        $this->redisBaseCluster->expire($redisKey, 86400 * 3);
        if ($count >= 5) { // 超过5个时加入屏蔽用户中
            return true;
        }
        return false;
    }


    /**
     * 举报用户微信错误
     *
     * @param $userInfo
     * @param $reportUid
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/1 16:45
     */
    public function reportUserWx($userInfo, $reportUid)
    {
        $reportUserWx = User::find()->select(['wx'])->where(['id' => $reportUid])->scalar();
        $data = [
            'uid' => $userInfo['uid'],
            'report_uid' => $reportUid,
            'report_user_wx' => $reportUserWx,
            'status' => 0,
            'created_at' => time()
        ];

        $reportModel = new Report();
        $reportModel->attributes = $data;
        if ($reportModel->save()) {
            return ToolsHelper::funcReturn("举报成功", true);
        }
        return ToolsHelper::funcReturn("举报失败");
    }

    /**
     * 屏蔽展示的用户id
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/23 11:59
     */
    public function getDenyUserArr()
    {
        // 未绑定微信用户屏蔽
        $redisKey = RedisHelper::RK('noBindWxUserIdArr');
        $noBindUserArr = $this->redisBaseCluster->sMembers($redisKey);

        // 商家等
        $businessUidArr = Yii::$app->params['businessUidArr'];

        return array_merge($businessUidArr, $noBindUserArr);
    }

    /**
     * 去除屏蔽
     *
     * @param $uid
     *
     * @author     xudt
     * @date-time  2021/6/23 13:27
     */
    public function removeDenyUser($uid)
    {
        // 未绑定微信用户屏蔽
        $redisKey = RedisHelper::RK('noBindWxUserIdArr');
        $this->redisBaseCluster->sRem($redisKey, $uid);
    }


    /**
     * 记录用户喜好、每日搜索任务完成
     *
     * @param $uid
     * @param $keyword
     *
     *
     * @author     xudt
     * @date-time  2021/10/15 10:44
     */
    public function saveUserFavourite($uid, $keyword)
    {
        $now = time();
        $keyword = trim($keyword);

        // 写入数据库
        if (!empty($keyword)) {
            $searchKeywordModel = SearchKeyword::find()->where(['uid' => $uid, 'keyword' => $keyword])->one();
            if (empty($searchKeywordModel)) {
                $searchKeywordModel = new SearchKeyword();
                $searchKeywordModel->uid = $uid;
                $searchKeywordModel->keyword = $keyword;
                $searchKeywordModel->created_at = $now;
                $searchKeywordModel->save();
            }

            // 登录用户写入到redis中
            if (!empty($uid)) {
                $redisKey = RedisHelper::RK('userFavourite', $uid);
                $this->redisBaseCluster->zAdd($redisKey, $now, $keyword);
            }
        }
    }

    /**
     * 用户喜好
     *
     * @param $uid
     * @param $limit
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/18 10:10
     */
    public function getUserFavourite($uid, $limit = 10)
    {
        $redisKey = RedisHelper::RK('userFavourite', $uid);
        $dataList = $this->redisBaseCluster->zRevRange($redisKey, 0, $limit);
        $favouriteCategoryArr = [];
        $favouriteKeywordArr = [];
        foreach ($dataList as $value) {
            if (intval($value) > 0) {
                $favouriteCategoryArr[] = $value;
            } else {
                $favouriteKeywordArr[] = $value;
            }
        }
        return [
            $favouriteCategoryArr,
            $favouriteKeywordArr,
        ];
    }

    /**
     * 查询用户最近三天内的搜索词
     *
     * @param int $uid
     *
     * @return false|mixed|string|null
     *
     * @author     xudt
     * @date-time  2021/10/8 15:05
     */
    public function getUserBestFavourite($uid = 0)
    {
        $deadTime = strtotime("-3 days");
        if (!empty($uid)) {
            $redisKey = RedisHelper::RK('userFavourite', $uid);
            $keywordList = $this->redisBaseCluster->zRevRange($redisKey, 0, 10, true);
            if (!empty($keywordList)) {
                foreach ($keywordList as $keyword => $time) {
                    if ($time <= $deadTime || intval($keyword) > 0) {
                        continue;
                    }
                    return $keyword;
                }
            }
        }
        return "";
    }

    /**
     * 绑定手机号
     *
     * @param $uid
     * @param $phone
     * @param $verifyCode
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/9 14:33
     */
    public function bindPhone($uid, $phone, $verifyCode)
    {
        $now = time();
        $sendPhoneCodeService = new SendPhoneCodeService();
        $verifyRes = $sendPhoneCodeService->verifyCode($phone, 2, $verifyCode);
        if (!$verifyRes['result']) {
            return $verifyRes;
        }

        // 判断手机号是否已绑定
        $phoneExist = User::find()->where(['phone' => $phone])->limit(1)->exists();
        if ($phoneExist) {
            return ToolsHelper::funcReturn("手机号已被占用");
        }

        //修改数据库
        $userRes = User::updateAll(
            [
                'phone' => $phone,
                'updated_at' => $now
            ],
            ['id' => $uid]
        );
        if ($userRes) {
            //更改用户redis
            $userRedisRes = $this->updateStructureDataToUserInfoRedis($uid, ['phone' => $phone]);
            if ($userRedisRes) {
                return ToolsHelper::funcReturn("绑定成功", true);
            }
        }
        return ToolsHelper::funcReturn("绑定失败");
    }
}