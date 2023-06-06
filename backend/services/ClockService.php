<?php
/**
 * 后台打卡服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:58
 */

namespace backend\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Clock;
use common\models\elasticsearch\EsClock;
use common\models\User;
use common\services\MessageService;
use common\services\ClockService as CommonClockService;
use common\services\RewardPointService;
use console\services\jobs\MessageJob;
use Yii;
use yii\helpers\ArrayHelper;

class ClockService
{
    /**
     * 获取商品列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 11:29
     */
    public function getClockList($data = [])
    {
        $clockModel = Clock::find();
        if(!empty($data['start_date']) && !empty($data['end_date'])){
            $clockModel->andWhere(['>=', 'updated_at', $data['start_date']])->andWhere(['<=', 'updated_at', $data['end_date']]);
        }
        if (!empty($data['uid'])) {
            $clockModel->andWhere(['uid' => $data['uid']]);
        }
        if ($data['status'] != -1) {
            $clockModel->andWhere(['status' => $data['status']]);
        }
        $clockCountModel = clone $clockModel;
        $count = $clockCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $clockModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        $reasonList = Yii::$app->params['clockReasonList'];

        // 获取用户信息
        $uidArr = ArrayHelper::getColumn($list, 'uid');
        $userArr = [];
        if (!empty($uidArr)) {
            $userArr = User::find()->select(['nickname'])->where(['id' => $uidArr])->asArray()->indexBy('id')->column();
        }

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $imageUrl = [];
                foreach ($picList as $url) {
                    $imageUrl[] = ToolsHelper::getLocalImg($url, '', 540);
                }

                $reasonId = array_search($value['audit_reason'], $reasonList);
                $value['reason_id'] = intval($reasonId);
                $value['clock_title'] = $value['title'];
                $value['pics'] = $imageUrl;
                $value['nickname'] = isset($userArr[$value['uid']]) ? $userArr[$value['uid']] : '';
                $value['status_name'] = Yii::$app->params['clockStatus'][$value['status']];
                $value['updated_at'] = !empty($value['updated_at']) ? date("Y-m-d H:i:s", $value['updated_at']) : '';
            }
        }

        return ToolsHelper::funcReturn(
            "打卡列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 审核通过
     *
     * @param     $id
     * @param int $isCheat
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/18 15:52
     */
    public function pass($id, $isCheat = 0)
    {
        $now = time();
        $clockModel = Clock::find()->where(['id' => $id])->one();
        if (empty($clockModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($clockModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 修改数据库
        $clockModel->status = CommonClockService::STAUTS_PASS;
        $clockModel->audit_at = $now;
        $clockModel->updated_at = $now;
        if ($clockModel->save()) {
            //写入geoRedis
            $commonClockService = new CommonClockService();
            $commonClockService->addClockGeoData($id, $clockModel->lat, $clockModel->lng);

            // 生成到ES
            $data = $clockModel->attributes;

            $userInfo = User::find()->where(['id' => $clockModel->uid])->asArray()->one();
            $data['nickname'] = $userInfo['nickname'];
            $data['avatar'] = $userInfo['avatar'];
            $data['gender'] = $userInfo['gender'];

            $clockEsData = EsClock::get($data['id']);
            if (empty($clockEsData)) { // 新增
                $data['price'] = floatval($clockModel->price);
                if (EsClock::insert($data['id'], $data)) {
                    $rewardPoint = 0;
                    if (empty($isCheat)) {
                        // 发布宝贝，审核通过则进行增加积分
                        $rewardPointService = new RewardPointService(RewardPointService::PUBLISH_CLOCK_AWARD_TYPE, $clockModel->uid, $now);
                        $rewardRes = $rewardPointService->awardPoint();
                        if ($rewardRes['result']) {
                            $rewardPoint = $rewardRes['data']['point'];
                        }
                    }

                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => MessageService::SYSTEM_USER
                                        ],
                                        'clockId' => $clockModel->id,
                                        'messageType' => MessageService::SYSTEM_CLOCK_AUDIT_PASS_MESSAGE,
                                        'rewardPoint' => $rewardPoint,
                                        'isCheat' => $isCheat,
                                    ]
                                ]
                            ]
                        )
                    );

                    return ToolsHelper::funcReturn("操作成功", true, ['reward_point' => $rewardPoint]);
                }
            } else { // 修改
                unset($data['updated_at']); // 修改不更新ES里的更新时间
                $data['price'] = floatval($clockModel->price);
                if (EsClock::update($data['id'], $data)) {
                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => MessageService::SYSTEM_USER
                                        ],
                                        'productId' => $clockModel->id,
                                        'messageType' => MessageService::SYSTEM_CLOCK_AUDIT_PASS_MESSAGE,
                                    ]
                                ]
                            ]
                        )
                    );

                    return ToolsHelper::funcReturn("操作成功", true);
                }
            }
            return ToolsHelper::funcReturn("数据库写入成功，ES更新失败", true);
        }
        return ToolsHelper::funcReturn("操作失败", true);
    }

    /**
     * 审核不通过
     *
     * @param $id
     * @param $auditReason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:01
     */
    public function refuse($id, $auditReason)
    {
        $now = time();
        $clockModel = Clock::find()->where(['id' => $id])->one();
        if (empty($clockModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($clockModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 修改数据库
        $clockModel->status = CommonClockService::STATUS_REFUSE;
        $clockModel->audit_reason = $auditReason;
        $clockModel->audit_at = $now;
        $clockModel->updated_at = $now;
        if ($clockModel->save()) {
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => MessageService::SYSTEM_USER
                                ],
                                'clockId' => $clockModel->id,
                                'messageType' => MessageService::SYSTEM_CLOCK_AUDIT_REFUSE_MESSAGE,
                                'audit_reason' => $auditReason
                            ]
                        ]
                    ]
                )
            );
            return ToolsHelper::funcReturn("审核不通过成功", true);
        }
        return ToolsHelper::funcReturn("审核不通过失败", true);
    }

    /**
     * 强制下架并删除
     *
     * @param $id
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/9 19:58
     */
    public function down($id)
    {
        $now = time();
        $clockModel = Clock::find()->where(['id' => $id])->one();
        if (empty($clockModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($clockModel->status != 1) {
            return ToolsHelper::funcReturn("非已上线商品");
        }

        // 修改数据库
        $clockModel->status = CommonClockService::STATUS_DEL;
        $clockModel->audit_reason = "强制下架并删除";
        $clockModel->audit_at = $now;
        $clockModel->updated_at = $now;
        if ($clockModel->save()) {
            $data = [
                'status' => $clockModel->status,
                'updated_at' => $clockModel->updated_at
            ];
            if (EsClock::update($id, $data)) {
                // 发送系统消息
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => [
                                [
                                    'userInfo' => [
                                        'uid' => MessageService::SYSTEM_USER
                                    ],
                                    'clockId' => $clockModel->id,
                                    'messageType' => MessageService::SYSTEM_CLOCK_AUDIT_DOWN_MESSAGE,
                                ]
                            ]
                        ]
                    )
                );
                return ToolsHelper::funcReturn("操作成功", true);
            }
        }
    }
}