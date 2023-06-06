<?php
/**
 * 积分管理
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:53
 */

namespace backend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\MessageService;
use common\services\RewardPointService;
use console\services\jobs\MessageJob;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class RewardPointController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ]
                    ],
                ],
            ],
            parent::behaviors()
        );
    }

    /**
     * 客服调整积分
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/8 16:41
     */
    public function actionRewardPoint()
    {
        $point = Yii::$app->request->post('point'); // 都为正数
        $uid = Yii::$app->request->post('uid');
        $awardType = Yii::$app->request->post('type'); // 101 加、102 减 103 增加排行榜奖励积分
        if (empty($point) || empty($uid) || empty($awardType)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $now = time();

        $rewardPointService = new RewardPointService($awardType, $uid, $now);
        $result = $rewardPointService->awardPoint($point);
        if ($result['result'] && $awardType == RewardPointService::RANK_AWARD_TYPE) { // 排行榜奖励发送系统消息
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => $uid
                                ],
                                'prizeInfo' => [
                                    'point' => $point
                                ],
                                'messageType' => MessageService::SYSTEM_POINT_AWARD_MESSAGE
                            ]
                        ]
                    ]
                )
            );
        }
        return $result;
    }
}