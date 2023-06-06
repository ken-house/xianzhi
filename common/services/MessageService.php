<?php
/**
 * 消息服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/3/10 13:53
 */

namespace common\services;

use common\helpers\ToolsHelper;
use common\models\mongo\MongodbMessageDetailRecord;
use common\models\mongo\MongodbMessageRecord;
use common\models\Product;
use common\models\TemplateMsgRecord;
use common\models\User;
use Yii;

class MessageService
{
    const SYSTEM_USER = 1; //系统用户

    const DIALOGUE_MESSAGE = 0; //对话消息

    // 增加类型时，全局配置params.php也要增加
    const SYSTEM_THUMB_MESSAGE = 1; //点赞消息
    const SYSTEM_COMMENT_MESSAGE = 2; //评论消息
    const SYSTEM_REPLY_MESSAGE = 3; // 回复评论消息
    const SYSTEM_AUDIT_PASS_MESSAGE = 4; // 商品审核通过消息
    const SYSTEM_AUDIT_REFUSE_MESSAGE = 5; //商品审核不通过消息
    const SYSTEM_POINT_EXCHANGE_MESSAGE = 6; // 积分兑换消息
    const SYSTEM_INVITE_FRIEND_MESSAGE = 7; //邀请好友积分到账消息
    const SYSTEM_AUDIT_DOWN_MESSAGE = 8; // 商品强制下架消息
    const SYSTEM_POINT_AWARD_MESSAGE = 9; // 积分奖励消息
    const SYSTEM_CLOCK_AUDIT_PASS_MESSAGE = 10; // 打卡审核通过消息
    const SYSTEM_CLOCK_AUDIT_REFUSE_MESSAGE = 11; // 打卡审核不通过消息
    const SYSTEM_CLOCK_AUDIT_DOWN_MESSAGE = 12; // 打卡强制下架消息
    const SYSTEM_CLOCK_VIEW_AWARD_MESSAGE = 13; // 打卡浏览数奖励消息
    const SYSTEM_ORDER_FANLI_SUCCESS_MESSAGE = 14; // 订单返利到账消息
    const SYSTEM_ORDER_FANLI_APPLY_MESSAGE = 15; // 订单返利申请成功
    const SYSTEM_POINT_DUE_MESSAGE = 16; // 积分过期消息
    const SYSTEM_JOB_AUDIT_PASS_MESSAGE = 17; // 兼职审核通过消息
    const SYSTEM_JOB_AUDIT_REFUSE_MESSAGE = 18; // 兼职审核不通过消息
    const SYSTEM_JOB_AUDIT_DOWN_MESSAGE = 19; // 兼职强制下架消息

    const UNREAD_STATUS = 0; //未读状态
    const READ_STATUS = 1; //已读状态

    private $uid; // 用户uid
    private $productId; //商品Id

    public function __construct($uid = 0, $productId = 0)
    {
        $this->uid = intval($uid);
        $this->productId = intval($productId);
    }

    /**
     * 生成用户消息密钥
     *
     * @param $senderUid
     * @param $getterUid
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/3/10 20:47
     */
    public function getMessageKey($senderUid, $getterUid)
    {
        if ($senderUid == self::SYSTEM_USER) { //系统消息归类为一条
            return '-' . self::SYSTEM_USER . '-' . $getterUid . '-0-';
        }
        return $senderUid > $getterUid ? '-' . $getterUid . '-' . $senderUid . '-' . $this->productId . "-" : '-' . $senderUid . '-' . $getterUid . '-' . $this->productId . "-";
    }

    /**
     * 获取消息列表
     *
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\mongodb\ActiveRecord
     *
     * @author     xudt
     * @date-time  2021/3/10 14:40
     */
    public function getList($page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $list = MongodbMessageRecord::find()->where(['LIKE', '_id', '-' . $this->uid . '-'])->andWhere(['<>', '_id', '-1-' . $this->uid . '-0-'])->andWhere(['<>', 'delete_uid', $this->uid])->orderBy('updated_at desc')->offset($start)->limit($pageSize)->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $messageKey = $value['_id'];
                $messageKeyArr = explode('-', trim($messageKey, '-'));
                $productId = intval($messageKeyArr[2]); // 商品id
                $otherUid = $this->uid == $messageKeyArr[0] ? intval($messageKeyArr[1]) : intval($messageKeyArr[0]); // 发送着或接收者
                $value['message_key'] = $value['_id'];
                $value['send_time'] = ToolsHelper::getTimeStrDiffNow($value['updated_at']);
                $value['updated_at'] = date("Y-m-d H:i:s", $value['updated_at']);

                // 查询未读数量
                MongodbMessageDetailRecord::resetTableName($messageKey);
                $mongodbMessageDetailRecordModel = MongodbMessageDetailRecord::find()->where(['getter_uid' => $this->uid, 'sender_uid' => $otherUid, 'status' => self::UNREAD_STATUS])->andWhere(['product_id' => $productId]);
                $value['unread_num'] = $mongodbMessageDetailRecordModel->count();


                // 获取商品信息
                $productInfo = Product::find()->select(['price', 'pics'])->where(['id' => $productId])->asArray()->one();
                $value['price'] = $productInfo['price'];
                $picArr = json_decode($productInfo['pics'], true);
                $value['cover'] = ToolsHelper::getLocalImg($picArr[0], '', 240);

                //获取接收者信息
                $userInfo = User::find()->select(['nickname', 'avatar'])->where(['id' => $otherUid])->asArray()->one();
                $value['nickname'] = $userInfo['nickname'];
                $value['avatar'] = $userInfo['avatar'];

                unset($value['_id'], $value['delete_uid']);
            }
        }
        return array_values($list);
    }

    /**
     * 系统消息
     *
     * @return array|\yii\mongodb\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/9/13 19:30
     */
    public function getLatestSystemMessageData()
    {
        $systemMessageData = MongodbMessageRecord::find()->where(['_id' => '-1-' . $this->uid . '-0-'])->asArray()->one();
        if (empty($systemMessageData)) {
            return [
                'unread_num' => 0,
                'content' => '暂无通知消息',
                'send_time' => '',
            ];
        }
        $sendTime = ToolsHelper::getTimeStrDiffNow($systemMessageData['updated_at']);
        $messageKey = $systemMessageData['_id'];
        MongodbMessageDetailRecord::resetTableName($messageKey);
        $unreadNum = $mongodbMessageDetailRecordModel = MongodbMessageDetailRecord::find()->where(['getter_uid' => $this->uid, 'sender_uid' => self::SYSTEM_USER, 'status' => self::UNREAD_STATUS])->count();
        return [
            'unread_num' => $unreadNum,
            'content' => $systemMessageData['content'],
            'send_time' => $sendTime,
        ];
    }

    /**
     * 未读消息数（不完全准确）
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/3/14 16:27
     */
    public function getUnreadNum()
    {
        $totalUnreadNum = 0;
        $list = MongodbMessageRecord::find()->where(['LIKE', '_id', '-' . $this->uid . '-'])->andWhere(['<>', 'delete_uid', $this->uid])->orderBy('updated_at desc')->limit(20)->asArray()->all();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $messageKey = $value['_id'];
                // 查询未读数量
                MongodbMessageDetailRecord::resetTableName($messageKey);
                $unreadNum = MongodbMessageDetailRecord::find()->where(['getter_uid' => $this->uid, 'status' => self::UNREAD_STATUS])->count();
                $totalUnreadNum += $unreadNum;
            }
        }
        return $totalUnreadNum;
    }

    /**
     * 消息详细记录
     *
     * @param $otherUid
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\mongodb\ActiveRecord
     *
     * @author     xudt
     * @date-time  2021/3/10 21:42
     */
    public function getDetailList($otherUid, $page, $pageSize)
    {
        $uidArr = [];
        $uidArr[] = $this->uid;
        $uidArr[] = $otherUid;

        $start = ($page - 1) * $pageSize;

        $messageKey = $this->getMessageKey($this->uid, $otherUid);
        MongodbMessageDetailRecord::resetTableName($messageKey);
        $messageList = MongodbMessageDetailRecord::find()->where(['sender_uid' => $uidArr, 'product_id' => $this->productId])->offset($start)->limit($pageSize)->orderBy('created_at desc')->asArray()->all();
        $lastestMessageId = '';
        if (!empty($messageList)) {
            foreach ($messageList as $key => &$value) {
                $value['message_id'] = "message_" . (string)$value['_id'];
                $value['send_time'] = date("Y-m-d H:i", $value['created_at']);
                unset($value['_id'], $value['created_at'], $value['getter_uid'], $value['product_id']);
                if ($key == 0) {
                    $lastestMessageId = $value['message_id'];
                }
            }
        }
        return [
            'messageList' => array_reverse($messageList),
            'lastestMessageId' => $lastestMessageId
        ];
    }

    /**
     * 全部设置为已读
     *
     * @param $messageKey
     * @param $otherUid
     * @param $buyerUid
     * @param $salerUid
     *
     * @author     xudt
     * @date-time  2021/3/10 21:59
     */
    public function readMessage($messageKey, $otherUid, $buyerUid = 0, $salerUid = 0)
    {
        MongodbMessageDetailRecord::resetTableName($messageKey);
        $where = ['getter_uid' => $this->uid, 'sender_uid' => $otherUid, 'product_id' => $this->productId];
        if ($otherUid == self::SYSTEM_USER) {
            $where = ['getter_uid' => $this->uid, 'sender_uid' => $otherUid];
        }
        $res = MongodbMessageDetailRecord::updateAll(['status' => self::READ_STATUS], $where);
        if ($res) {
            if (!empty($this->productId)) { // 对话消息
                if ($this->uid == $salerUid) { // 当前操作者为卖家，将买家回复或想要的置为已读
                    TemplateMsgRecord::updateAll(['is_read' => self::READ_STATUS], ['saler_uid' => $salerUid, 'buyer_uid' => $buyerUid, 'product_id' => $this->productId, 'type' => [TemplateMsgService::WANT_PRODUCT_TMP_MSG, TemplateMsgService::BUYER_REPLY_TMP_MSG]]);
                } else { // 当前操作者为买家，将卖家回复的置为已读
                    TemplateMsgRecord::updateAll(['is_read' => self::READ_STATUS], ['saler_uid' => $salerUid, 'buyer_uid' => $buyerUid, 'product_id' => $this->productId, 'type' => TemplateMsgService::SALER_REPLY_TMP_MSG]);
                }
            } else { // 系统消息已读，将审核通过、不通过、下架置为已读
                TemplateMsgRecord::updateAll(['is_read' => self::READ_STATUS], ['saler_uid' => $this->uid, 'buyer_uid' => MessageService::SYSTEM_USER, 'type' => [TemplateMsgService::PRODUCT_AUDIT_PASS_TMP_MSG, TemplateMsgService::PRODUCT_AUDIT_REFUSE_TMP_MSG, TemplateMsgService::PRODUCT_AUDIT_DOWN_TMP_MSG]]);
            }
        }
    }

    /**
     * 发送消息
     *
     * @param $senderUid
     * @param $getterUid
     * @param $messageInfo
     * @param $type
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/10 20:48
     */
    public function send($senderUid, $getterUid, $messageInfo, $type = self::DIALOGUE_MESSAGE)
    {
        $now = time();
        $messageKey = $this->getMessageKey($senderUid, $getterUid);
        $messageRecordModel = MongodbMessageRecord::find()->where(['_id' => $messageKey])->one();
        if (empty($messageRecordModel)) {
            $messageRecordModel = new MongodbMessageRecord();
            $messageRecordModel->_id = $messageKey;
        }
        $messageRecordModel->type = $type;
        $messageRecordModel->content = $messageInfo['content'];
        $messageRecordModel->delete_uid = 0;
        $messageRecordModel->updated_at = $now;
        if ($messageRecordModel->save()) {
            MongodbMessageDetailRecord::resetTableName($messageKey);
            $messageDetailRecordModel = new MongodbMessageDetailRecord();
            $messageDetailRecordModel->type = intval($type);
            $messageDetailRecordModel->sender_uid = intval($senderUid);
            $messageDetailRecordModel->getter_uid = intval($getterUid);
            $messageDetailRecordModel->product_id = intval($this->productId);
            $messageDetailRecordModel->content = $messageInfo['content'];
            $messageDetailRecordModel->link_url = "";
            if (!empty($messageInfo['link_url'])) {
                $messageDetailRecordModel->link_url = $messageInfo['link_url'];
            }
            $messageDetailRecordModel->status = self::UNREAD_STATUS;
            $messageDetailRecordModel->created_at = $now;
            if ($messageDetailRecordModel->save()) {
                $messageId = "message_" . (string)$messageDetailRecordModel->_id;
                $data = array_merge(
                    $messageDetailRecordModel->attributes,
                    [
                        'send_time' => date("Y-m-d H:i", $now),
                        'message_id' => $messageId,
                        'message_key' => $messageKey
                    ]
                );
                unset($data['_id'], $data['created_at'], $data['product_id']);
                return ToolsHelper::funcReturn("消息发送成功", true, $data);
            }
        }
        return ToolsHelper::funcReturn("消息发送失败");
    }

    /**
     * 第一个人删除时，delete_uid记录删除用户uid，第二个人删除时，删除该消息及对话消息
     *
     * @param $messageKey
     * @param $otherUid
     *
     * @throws \yii\db\StaleObjectException
     *
     * @author     xudt
     * @date-time  2021/3/11 10:13
     */
    public function delete($messageKey, $otherUid)
    {
        $messageRecordModel = MongodbMessageRecord::find()->where(['_id' => $messageKey])->one();
        if (!empty($messageRecordModel)) {
            if (!empty($messageRecordModel->delete_uid) && $messageRecordModel->delete_uid != $this->uid) { // 两人都删除
                if ($messageRecordModel->delete()) {
                    $uidArr = [];
                    $uidArr[] = $this->uid;
                    $uidArr[] = $otherUid;

                    //删除详情记录
                    MongodbMessageDetailRecord::resetTableName($messageKey);
                    MongodbMessageDetailRecord::deleteAll(['sender_uid' => $uidArr, 'product_id' => $this->productId]);
                    return ToolsHelper::funcReturn("删除成功", true);
                }
            } else { // 一人删除
                $messageRecordModel->delete_uid = $this->uid;
                if ($messageRecordModel->save()) {
                    return ToolsHelper::funcReturn("删除成功", true);
                }
            }
        }
        return ToolsHelper::funcReturn("删除失败");
    }

    /**
     * 系统消息通知
     *
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\mongodb\ActiveRecord
     *
     * @author     xudt
     * @date-time  2021/3/16 21:49
     */
    public function getSystemMessageList($page, $pageSize)
    {
        $messageTypeArr = Yii::$app->params['messageType'];
        $start = ($page - 1) * $pageSize;

        $messageKey = $this->getMessageKey(self::SYSTEM_USER, $this->uid);
        MongodbMessageDetailRecord::resetTableName($messageKey);
        $messageList = MongodbMessageDetailRecord::find()->where(['sender_uid' => self::SYSTEM_USER, 'getter_uid' => $this->uid])->offset($start)->limit($pageSize)->orderBy('created_at desc')->asArray()->all();
        $lastestMessageId = '';
        if (!empty($messageList)) {
            foreach ($messageList as $key => &$value) {
                $value['message_id'] = "message_" . (string)$value['_id'];
                $value['type_name'] = $messageTypeArr[$value['type']];
                $value['send_time'] = date("Y-m-d H:i", $value['created_at']);
                $value['cover'] = '';
                if (in_array($value['type'], [MessageService::SYSTEM_CLOCK_AUDIT_PASS_MESSAGE, MessageService::SYSTEM_CLOCK_AUDIT_REFUSE_MESSAGE, MessageService::SYSTEM_CLOCK_AUDIT_DOWN_MESSAGE, MessageService::SYSTEM_CLOCK_VIEW_AWARD_MESSAGE])) {
                    $clockService = new ClockService();
                    $clockInfo = $clockService->getClockInfo($value['product_id']);
                    $value['cover'] = isset($clockInfo['cover']) ? $clockInfo['cover'] : '';
                } elseif (in_array($value['type'], [MessageService::SYSTEM_JOB_AUDIT_PASS_MESSAGE, MessageService::SYSTEM_JOB_AUDIT_REFUSE_MESSAGE, MessageService::SYSTEM_JOB_AUDIT_DOWN_MESSAGE])) {
                    $parttimeJobService = new ParttimeJobService();
                    $jobInfo = $parttimeJobService->getJobInfo($value['product_id']);
                    $value['cover'] = isset($jobInfo['cover']) ? $jobInfo['cover'] : '';
                } elseif (!in_array($value['type'], [MessageService::SYSTEM_POINT_EXCHANGE_MESSAGE, MessageService::SYSTEM_INVITE_FRIEND_MESSAGE])) {
                    $productService = new ProductService();
                    $productInfo = $productService->getProductInfo($value['product_id']);
                    $value['cover'] = isset($productInfo['cover']) ? $productInfo['cover'] : '';
                }
                unset($value['_id'], $value['created_at'], $value['getter_uid'], $value['type']);
                if ($key == 0) {
                    $lastestMessageId = $value['message_id'];
                }
            }
        }
        return [
            'messageList' => array_reverse($messageList),
            'lastestMessageId' => $lastestMessageId
        ];
    }
}