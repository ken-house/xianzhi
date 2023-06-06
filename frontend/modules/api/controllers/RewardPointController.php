<?php
/**
 * 积分管理
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/6 10:52
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\MessageService;
use common\services\PrizeService;
use common\services\RewardPointService;

use console\services\jobs\MessageJob;
use Yii;
use yii\helpers\ArrayHelper;

class RewardPointController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 积分记录
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 17:49
     */
    public function actionRecord()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $awardType = Yii::$app->request->get("award_type", 0); // 指定类型

        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $recordList = RewardPointService::getRewardPointRecord($uid, $awardType, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "积分记录",
            true,
            [
                'recordList' => $recordList,
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }

    /**
     * 奖品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 17:48
     */
    public function actionPrizeList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $prizeArr = Yii::$app->params['prizeArr'];
        // 获取所有奖品的兑换情况
        $prizeService = new PrizeService();

        $vipDiscount = $prizeService->getUserVipDiscount($userInfo['reward_point']);

        $exchangedArr = $prizeService->getAllPrizeExchangeNum();

        foreach ($prizeArr as $key => &$value) {
            if ($value['status'] != 1) {
                unset($prizeArr[$key]);
            }
            $coverUrl = ToolsHelper::getLocalImg($value['image_url']);
            $coverHeight = ToolsHelper::getCoverHeight($value['image_url']);

            $value['exchanged_num'] = isset($exchangedArr[$value['id']]) ? $exchangedArr[$value['id']] : 0;
            $value['image_url'] = $coverUrl;
            $value['image_height'] = $coverHeight;
            $value['point'] = $value['point'] * $vipDiscount;
            $value['vip_discount'] = $vipDiscount;
        }

        $shareInfo = [
            'title' => '积分免费兑换现金红包，手机充值卡，优酷、腾讯、爱奇艺视频vip会员，赶紧注册一个一起赚积分吧',
            'imageUrl' => '',
            'path' => '/pages/reward_point/exchange/exchange?invite_code=' . $userInfo['invite_code'],
            'params' => 'invite_code=' . $userInfo['invite_code']
        ];

        ArrayHelper::multisort($prizeArr, 'sort', SORT_ASC);

        // 已兑换奖品用户列表
        $noticeList = $prizeService->getExchangeUserList();

        return ToolsHelper::funcReturn(
            "奖品列表",
            true,
            [
                'userInfo' => $userInfo,
                'prizeArr' => array_values($prizeArr),
                'shareInfo' => $shareInfo,
                'noticeList' => $noticeList
            ]
        );
    }

    /**
     * 奖品详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 17:48
     */
    public function actionPrizeInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $id = Yii::$app->request->get('id');
        $prizeArr = ArrayHelper::index(Yii::$app->params['prizeArr'], 'id');
        $prizeInfo = isset($prizeArr[$id]) ? $prizeArr[$id] : [];
        if (empty($prizeInfo)) {
            return ToolsHelper::funcReturn("无此奖品");
        }
        if ($prizeInfo['status'] != 1) {
            return ToolsHelper::funcReturn("奖品已下架");
        }
        $prizeInfo['image_url'] = ToolsHelper::getLocalImg($prizeInfo['image_url']);
        // 已兑换个数
        $prizeService = new PrizeService();

        $vipDiscount = $prizeService->getUserVipDiscount($userInfo['reward_point']);
        $prizeInfo['point'] = $prizeInfo['point'] * $vipDiscount;
        $prizeInfo['vip_discount'] = $vipDiscount;

        $prizeInfo['exchanged_num'] = $prizeService->getPrizeExchangedNum($id);


        return ToolsHelper::funcReturn(
            "奖品详情",
            true,
            [
                'userInfo' => $userInfo,
                'prizeInfo' => $prizeInfo
            ]
        );
    }

    /**
     * 奖品兑换
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 17:49
     */
    public function actionExchange()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();
        $id = Yii::$app->request->post('id');
        $prizeArr = ArrayHelper::index(Yii::$app->params['prizeArr'], 'id');
        $prizeInfo = isset($prizeArr[$id]) ? $prizeArr[$id] : [];
        if (empty($prizeInfo)) {
            return ToolsHelper::funcReturn("无此奖品");
        }
        if ($prizeInfo['status'] != 1) {
            return ToolsHelper::funcReturn("奖品已下架");
        }

        // 检测是否完成首单任务
        $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE, $uid, $now);
        if (!$rewardPointService->isFinishOnceTask(RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE)) {
            return ToolsHelper::funcReturn("请先完成首单任务");
        }

        $prizeService = new PrizeService();

        $vipDiscount = $prizeService->getUserVipDiscount($userInfo['reward_point']);
        $prizeInfo['point'] = $prizeInfo['point'] * $vipDiscount;

        $exchangeRes = $prizeService->exchange($prizeInfo, $userInfo);
        if ($exchangeRes['result']) {
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => $userInfo,
                                'prizeInfo' => $prizeInfo,
                                'messageType' => MessageService::SYSTEM_POINT_EXCHANGE_MESSAGE
                            ]
                        ]
                    ]
                )
            );
        }
        return $exchangeRes;
    }

    /**
     * 发放奖励
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/17 11:36
     */
    public function actionDrawReward()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();
        $taskId = Yii::$app->request->post('task_id', 0);

        // 发放奖励
        $rewardPointService = new RewardPointService($taskId, $uid, $now);
        if ($taskId == RewardPointService::BIND_WX_AWARD_TYPE && $rewardPointService->isFinishOnceTask($taskId)) {
            return ToolsHelper::funcReturn("您已领取过该奖励");
        }
        $rewardRes = $rewardPointService->awardPoint();
        if ($rewardRes['result']) {
            return ToolsHelper::funcReturn("奖励发送成功", true, ['rewardTips' => "领取成功，" . $rewardRes['data']['point'] . "积分已放入账户"]);
        }
        return ToolsHelper::funcReturn("奖励发放失败");
    }
}