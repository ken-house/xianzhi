<?php
/**
 * 绑定好友关系服务类
 *
 * @author xudt
 * @date   : 2019/12/4 17:31
 */

namespace common\services;

use common\helpers\ApcuHelper;
use common\helpers\ToolsHelper;
use common\models\User;
use common\models\UserData;
use console\services\jobs\CashJob;
use console\services\jobs\UserEffectJob;
use Yii;
use yii\db\Exception;

class InviteService
{
    /**
     * 获取好友列表
     *
     * @param     $uid
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/12/4 18:00
     *
     */
    public function getFriendListByUid($uid,$page=1,$pageSize=20)
    {
        $start = ($page - 1) * $pageSize;
        $friendList = User::find()->select(['id', 'nickname', 'avatar', 'gender', 'invite_at'])->where(['invite_uid' => $uid])->orderBy("invite_at desc")->offset($start)->limit($pageSize)->asArray()->all();
        if (!empty($friendList)) {
            foreach ($friendList as $key => &$value) {
                $value['uid'] = $value['id'];
                $value['avatar'] = ToolsHelper::getLocalImg($value['avatar'], Yii::$app->params['defaultAvatar'], 240);
                $value['invite_at'] = date("Y-m-d H:i:s", $value['invite_at']);
            }
        }

        return $friendList;
    }

    /**
     * 填写邀请码，绑定好友关系
     *
     * @param $uid
     * @param $userInfo
     * @param $inviteCode
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/12/5 16:34
     *
     */
    public function bindFriendShip($uid, $userInfo, $inviteCode)
    {
        $now = time();
        if (empty($inviteCode)) {
            return ToolsHelper::funcReturn("请输入邀请码");
        }
        if (!empty($userInfo['invite_uid'])) {
            return ToolsHelper::funcReturn("已填写过邀请码");
        }
        if ($inviteCode == $userInfo['invite_code']) {
            return ToolsHelper::funcReturn("不可输入自己的邀请码");
        }
        //根据invite_code查询用户id
        $inviteUserData = UserData::find()->select(['uid', 'invite_friend_num'])->where(['invite_code' => $inviteCode])->asArray()->one();
        if ($inviteUserData['uid'] == 0) {
            return ToolsHelper::funcReturn("邀请码不存在");
        }
        $inviteUid = $inviteUserData['uid'];


        $transaction = Yii::$app->db->beginTransaction();
        try {
            //更新注册用户信息
            $uidData = [
                'invite_uid' => $inviteUid,
                'invite_at' => $now,
                'updated_at' => $now
            ];
            $userDataRes = User::updateAll($uidData, ['id' => $uid]);

            //更新邀请者信息
            $inviteUserRow = UserData::updateAllCounters(['invite_friend_num' => 1], ['uid' => $inviteUid]);

            if ($userDataRes && $inviteUserRow) {
                $transaction->commit();

                //更新注册者redis
                $userService = new UserService();
                $userRedisRes = $userService->updateStructureDataToUserInfoRedis($uid, $uidData);
                if (!$userRedisRes) {
                    Yii::info(
                        [
                            'data' => [
                                'uid' => $uid,
                                'userDataRedis' => $userInfo,
                                'inviteCode' => $inviteCode
                            ],
                            'error' => '更新注册者redis出错'
                        ],
                        "inviteFriend"
                    );
                }

                //更新邀请者redis
                $inviteUserRedisRes = $userService->updateStructureDataToUserInfoRedis($inviteUid, ['invite_friend_num' => $inviteUserData['invite_friend_num'] + 1]);
                if (!$inviteUserRedisRes) {
                    Yii::info(
                        [
                            'data' => [
                                'uid' => $uid,
                                'userDataRedis' => $userInfo,
                                'inviteCode' => $inviteCode
                            ],
                            'error' => '更新邀请者redis出错'
                        ],
                        "inviteFriend"
                    );
                }

                // 发放积分
                $rewardPointService = new RewardPointService(RewardPointService::INVITE_AWARD_TYPE, $inviteUid, $now);
                $rewardRes = $rewardPointService->awardPoint();
                if (!$rewardRes['result']) {
                    Yii::info(
                        [
                            'data' => [
                                'uid' => $uid,
                                'userDataRedis' => $userInfo,
                                'inviteCode' => $inviteCode
                            ],
                            'error' => $rewardRes['message']
                        ],
                        "inviteFriend"
                    );
                }

                return ToolsHelper::funcReturn("绑定好友关系成功", true, array_merge($userInfo, $uidData));
            } else {
                $transaction->rollBack();
                Yii::info(
                    [
                        'data' => [
                            'uid' => $uid,
                            'userDataRedis' => $userInfo,
                            'inviteCode' => $inviteCode
                        ],
                        'error' => '写入数据出错'
                    ],
                    "inviteFriend"
                );
                return ToolsHelper::funcReturn("绑定好友关系失败");
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::info(
                [
                    'data' => [
                        'uid' => $uid,
                        'userDataRedis' => $userInfo,
                        'inviteCode' => $inviteCode
                    ],
                    'error' => '程序错误,错误原因：' . $e->getMessage()
                ],
                "inviteFriend"
            );
            return ToolsHelper::funcReturn("绑定好友关系失败");
        }
    }
}