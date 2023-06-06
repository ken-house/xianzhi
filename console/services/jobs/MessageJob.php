<?php
/**
 * 消息队列消费者
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/3/15 11:52
 */

namespace console\services\jobs;

use common\services\ParttimeJobService;
use common\services\ClockService;
use common\services\MessageService;
use common\services\ProductService;
use common\services\RewardPointService;
use yii\console\ExitCode;

use Yii;

class MessageJob extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $data;

    /**
     * 系统消息消费
     *
     * @param \yii\queue\Queue $queue
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/12/18 13:14
     *
     */
    public function execute($queue)
    {
        try {
            if (!empty($this->data)) {
                foreach ($this->data as $key => $message) {
                    $sendRes = $this->sendMessage($message);
                    if (!$sendRes) { //无效或失败
                        continue;
                    }
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info(
                [
                    'data' => $this->data,
                    'error' => $e->getMessage()
                ],
                'messageSendConsumer'
            );
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 发送单条消息
     *
     * @param $message
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/3/16 15:30
     */
    private function sendMessage($message)
    {
        switch ($message['messageType']) {
            case MessageService::SYSTEM_POINT_EXCHANGE_MESSAGE:
            case MessageService::SYSTEM_INVITE_FRIEND_MESSAGE:
            case MessageService::SYSTEM_POINT_AWARD_MESSAGE:
                $sendRes = $this->sendUserMessage($message);
                break;
            case MessageService::SYSTEM_CLOCK_AUDIT_PASS_MESSAGE:
            case MessageService::SYSTEM_CLOCK_AUDIT_REFUSE_MESSAGE:
            case MessageService::SYSTEM_CLOCK_AUDIT_DOWN_MESSAGE:
            case MessageService::SYSTEM_CLOCK_VIEW_AWARD_MESSAGE:
                $sendRes = $this->sendClockMessage($message);
                break;
            case MessageService::SYSTEM_JOB_AUDIT_PASS_MESSAGE:
            case MessageService::SYSTEM_JOB_AUDIT_REFUSE_MESSAGE:
            case MessageService::SYSTEM_JOB_AUDIT_DOWN_MESSAGE:
                $sendRes = $this->sendJobMessage($message);
                break;
            case MessageService::SYSTEM_ORDER_FANLI_SUCCESS_MESSAGE:
            case MessageService::SYSTEM_ORDER_FANLI_APPLY_MESSAGE:
                $sendRes = $this->sendOrderMessage($message);
                break;
            default: // 发送有关商品的系统消息
                $sendRes = $this->sendProductMessage($message);
        }
        return $sendRes;
    }

    /**
     * 发送和订单有关的系统消息
     *
     * @param $message
     *
     * @return false|mixed
     *
     * @author     xudt
     * @date-time  2021/10/15 23:16
     */
    public function sendOrderMessage($message)
    {
        $userInfo = $message['userInfo']; //当前登录用户
        $messageInfo = $this->getMessageInfoByType($message, $userInfo);
        if (empty($messageInfo['sender_uid'])) { //无效
            return false;
        }

        $messageService = new MessageService($userInfo['uid'], 0);
        $sendRes = $messageService->send($messageInfo['sender_uid'], $messageInfo['getter_uid'], $messageInfo, $message['messageType']);
        return $sendRes['result'];
    }

    /**
     * 发送和打卡相关的系统消息
     *
     * @param $message
     *
     * @author     xudt
     * @date-time  2021/7/19 20:23
     */
    private function sendClockMessage($message)
    {
        $userInfo = $message['userInfo']; //当前登录用户
        $clockId = $message['clockId']; //打卡id

        $clockService = new ClockService();
        $clockInfo = $clockService->getClockInfo($clockId);

        $messageInfo = $this->getMessageInfoByType($message, $userInfo, [], $clockInfo);
        if (empty($messageInfo['sender_uid'])) { //无效
            return false;
        }

        $messageService = new MessageService($userInfo['uid'], $clockId);
        $sendRes = $messageService->send($messageInfo['sender_uid'], $messageInfo['getter_uid'], $messageInfo, $message['messageType']);
        return $sendRes['result'];
    }

    /**
     * 发送兼职系统消息
     *
     * @param $message
     *
     * @return false|mixed
     *
     * @author     xudt
     * @date-time  2021/11/10 14:01
     */
    private function sendJobMessage($message)
    {
        $userInfo = $message['userInfo']; //当前登录用户
        $jobId = $message['jobId']; //打卡id

        $parttimeJobService = new ParttimeJobService();
        $jobInfo = $parttimeJobService->getJobInfo($jobId);

        $messageInfo = $this->getMessageInfoByType($message, $userInfo, [], [], $jobInfo);
        if (empty($messageInfo['sender_uid'])) { //无效
            return false;
        }

        $messageService = new MessageService($userInfo['uid'], $jobId);
        $sendRes = $messageService->send($messageInfo['sender_uid'], $messageInfo['getter_uid'], $messageInfo, $message['messageType']);
        return $sendRes['result'];
    }

    /**
     * 发送和用户相关的系统消息
     *
     * @param $message
     *
     * @return false|mixed
     *
     * @author     xudt
     * @date-time  2021/4/7 14:31
     */
    private function sendUserMessage($message)
    {
        $userInfo = $message['userInfo']; //当前登录用户

        $messageInfo = $this->getMessageInfoByType($message, $userInfo);
        if (empty($messageInfo['sender_uid'])) { //无效
            return false;
        }

        $messageService = new MessageService($userInfo['uid']);
        $sendRes = $messageService->send($messageInfo['sender_uid'], $messageInfo['getter_uid'], $messageInfo, $message['messageType']);
        return $sendRes['result'];
    }


    /**
     * 发送和商品相关的系统消息
     *
     * @param $message
     *
     * @return false|mixed
     *
     * @author     xudt
     * @date-time  2021/4/7 14:20
     */
    private function sendProductMessage($message)
    {
        $userInfo = $message['userInfo']; //当前登录用户
        $productId = $message['productId']; //商品id

        $productService = new ProductService();
        $productInfo = $productService->getProductInfo($productId);

        $messageInfo = $this->getMessageInfoByType($message, $userInfo, $productInfo);
        if (empty($messageInfo['sender_uid'])) { //无效
            return false;
        }

        $messageService = new MessageService($userInfo['uid'], $productId);
        $sendRes = $messageService->send($messageInfo['sender_uid'], $messageInfo['getter_uid'], $messageInfo, $message['messageType']);
        return $sendRes['result'];
    }


    /**
     * 根据类型获取消息内容及跳转地址
     *
     * @param $message
     * @param $userInfo
     * @param $productInfo
     * @param $clockInfo
     * @param $jobInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/16 15:35
     */
    private function getMessageInfoByType($message, $userInfo, $productInfo = [], $clockInfo = [], $jobInfo = [])
    {
        switch ($message['messageType']) {
            case MessageService::SYSTEM_THUMB_MESSAGE:
                $content = '用户“' . $userInfo['nickname'] . '”点赞了您发布的宝贝“' . $productInfo['name'] . '”。';
                $linkUrl = '/pages/product/product?id=' . $productInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $productInfo['uid'];
                break;
            case MessageService::SYSTEM_COMMENT_MESSAGE:
                $content = '用户“' . $userInfo['nickname'] . '”评价了您发布的宝贝“' . $productInfo['name'] . '”。';
                $linkUrl = '/pages/product/product?id=' . $productInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $productInfo['uid'];
                break;
            case MessageService::SYSTEM_REPLY_MESSAGE:
                $content = '用户“' . $userInfo['nickname'] . '”在宝贝”' . $productInfo['name'] . '”回复了您的评论。';
                $linkUrl = '/pages/product/product?id=' . $productInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $message['getterUid'];
                break;
            case MessageService::SYSTEM_AUDIT_PASS_MESSAGE:
                $content = '恭喜您！您发布的宝贝”' . $productInfo['name'] . '”审核通过！';
                if (!empty($message['isCheat'])) {
                    $content .= "检测到您之前已发布过类似该宝贝，无法赠送积分。如有疑问，请联系客服。";
                } elseif (!empty($message['rewardPoint'])) {
                    $content .= $message['rewardPoint'] . "积分已放入账户。";
                }

                $linkUrl = '/pages/product/product?id=' . $productInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $productInfo['uid'];
                break;
            case MessageService::SYSTEM_AUDIT_REFUSE_MESSAGE:
                $content = '您发布的宝贝”' . $productInfo['name'] . '”审核不通过。不通过原因：' . $message['audit_reason'] . "。";
                $linkUrl = '/pages/mycenter_product/mycenter_product';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $productInfo['uid'];
                break;
            case MessageService::SYSTEM_POINT_EXCHANGE_MESSAGE:
                $content = '您于' . date("Y-m-d H:i:s") . '使用' . $message['prizeInfo']['point'] . '积分兑换' . $message['prizeInfo']['title'] . '申请成功，请微信联系客服完成奖品发放！';
                $linkUrl = '/pages/reward_point/record/record';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['uid'];
                break;
            case MessageService::SYSTEM_INVITE_FRIEND_MESSAGE:
                $rewardPoint = Yii::$app->params['awardType'][11]['point'];
                $content = '您邀请的好友”' . $userInfo['nickname'] . '”注册成功！' . $rewardPoint . '积分已放入账户。';
                $linkUrl = '/pages/reward_point/record/record';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['invite_uid'];
                break;
            case MessageService::SYSTEM_AUDIT_DOWN_MESSAGE:
                $content = '您发布的宝贝”' . $productInfo['name'] . '”已被强制下架并删除，违规原因：' . $message['audit_reason'] . '。如有疑问，请联系客服。';
                $linkUrl = '/pages/mycenter_product/mycenter_product';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $productInfo['uid'];
                break;
            case MessageService::SYSTEM_POINT_AWARD_MESSAGE:
                $content = '恭喜您，获得每日积分排行榜奖励' . $message['prizeInfo']['point'] . '积分。';
                $linkUrl = '/pages/reward_point/record/record';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['uid'];
                break;
            case MessageService::SYSTEM_CLOCK_AUDIT_PASS_MESSAGE:
                $content = '恭喜您！您发布的打卡”' . $clockInfo['name'] . '”审核通过！';
                if (!empty($message['isCheat'])) {
                    $content .= "检测到您之前已发布过类似该打卡，无法赠送积分。如有疑问，请联系客服。";
                } elseif (!empty($message['rewardPoint'])) {
                    $content .= $message['rewardPoint'] . "积分已放入账户。";
                }
                $linkUrl = '/pages/clock/info/info?id=' . $clockInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $clockInfo['uid'];
                break;
            case MessageService::SYSTEM_CLOCK_AUDIT_REFUSE_MESSAGE:
                $content = '您发布的打卡”' . $clockInfo['name'] . '”审核不通过。不通过原因：' . $message['audit_reason'] . "。";
                $linkUrl = '/pages/clock/mycenter/mycenter';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $clockInfo['uid'];
                break;
            case MessageService::SYSTEM_CLOCK_AUDIT_DOWN_MESSAGE:
                $content = '您发布的打卡”' . $clockInfo['name'] . '”已被强制下架并删除。如有疑问，请联系客服。';
                $linkUrl = '/pages/clock/mycenter/mycenter';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $clockInfo['uid'];
                break;
            case MessageService::SYSTEM_CLOCK_VIEW_AWARD_MESSAGE:
                $content = '您发布的打卡”' . $clockInfo['name'] . '”浏览数已达' . $message['viewNum'] . '，奖励' . $message['rewardPoint'] . '积分已放入账户。';
                $linkUrl = '/pages/reward_point/record/record';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $clockInfo['uid'];
                break;
            case MessageService::SYSTEM_ORDER_FANLI_SUCCESS_MESSAGE:
                $content = '您的订单（订单号：' . $message['orderSn'] . '）返利到账，' . $message['rewardPoint'] . "积分已放入账户。";
                $linkUrl = '/pages/business_product_page/order/order';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['uid'];
                break;
            case MessageService::SYSTEM_ORDER_FANLI_APPLY_MESSAGE:
                $content = '您的订单（订单号：' . $message['orderSn'] . '）返利申请成功';
                if (!empty($message['settleDate'])) {
                    $content .= ",预计返利到账时间为" . $message['settleDate'] . "，可关注订单列表查看详情";
                }
                if (!empty($message['taskId'])) {
                    if ($message['taskId'] == RewardPointService::BUSINESS_FIRST_ORDER_AWARD_TYPE) {
                        $content .= "。该订单完成首单任务，奖励" . $message['rewardPoint'] . '积分已放入账户。';
                    } else {
                        $content .= "。该订单完成每日一单任务，奖励" . $message['rewardPoint'] . '积分已放入账户。';
                    }
                }
                if (!empty($message['orderNumTask'])) {
                    $content .= "完成" . $message['orderNumTask']['orderCount'] . "单任务，获得" . $message['orderNumTask']['rewardPoint'] . "积分已放入账户。";
                }
                $linkUrl = '/pages/business_product_page/order/order';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['uid'];
                break;
            case MessageService::SYSTEM_POINT_DUE_MESSAGE:
                $content = '您有' . $message['prizeInfo']['point'] . '积分已过期，积分有效期为一年，请尽早使用，避免积分过期。';
                $linkUrl = '/pages/reward_point/record/record';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $userInfo['uid'];
                break;
            case MessageService::SYSTEM_JOB_AUDIT_PASS_MESSAGE:
                $content = '恭喜您！您发布的兼职”' . $jobInfo['title'] . '”审核通过！';
                $linkUrl = '/pages/parttime_job/info/info?id=' . $jobInfo['id'];
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $jobInfo['uid'];
                break;
            case MessageService::SYSTEM_JOB_AUDIT_REFUSE_MESSAGE:
                $content = '您发布的兼职”' . $jobInfo['title'] . '”审核不通过。不通过原因：' . $message['audit_reason'] . "。";
                $linkUrl = '/pages/parttime_job/mycenter/mycenter';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $jobInfo['uid'];
                break;
            case MessageService::SYSTEM_JOB_AUDIT_DOWN_MESSAGE:
                $content = '您发布的兼职”' . $jobInfo['title'] . '”已被强制下架并删除。如有疑问，请联系客服。';
                $linkUrl = '/pages/parttime_job/mycenter/mycenter';
                $senderUid = MessageService::SYSTEM_USER;
                $getterUid = $jobInfo['uid'];
                break;
            default:
                $content = '';
                $linkUrl = '';
                $senderUid = 0;
                $getterUid = 0;
        }
        // 除积分兑换、积分奖励、订单返利外,若实际发起人和接收者为同一人，则丢弃
        if ($userInfo['uid'] == $getterUid && !in_array($message['messageType'], [MessageService::SYSTEM_POINT_EXCHANGE_MESSAGE, MessageService::SYSTEM_POINT_AWARD_MESSAGE, MessageService::SYSTEM_ORDER_FANLI_SUCCESS_MESSAGE, MessageService::SYSTEM_ORDER_FANLI_APPLY_MESSAGE])) {
            return [];
        }

        return [
            'content' => $content,
            'link_url' => $linkUrl,
            'sender_uid' => $senderUid,
            'getter_uid' => $getterUid,
        ];
    }
}