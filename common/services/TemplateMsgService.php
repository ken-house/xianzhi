<?php
/**
 * 模板消息服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/19 14:11
 */

namespace common\services;

use common\models\Product;
use common\models\TemplateMsgRecord;
use common\models\UnionOpenid;
use common\models\User;
use Yii;

class TemplateMsgService
{
    const WANT_PRODUCT_TMP_MSG = 1;  // 用户想要商品模板消息
    const SALER_REPLY_TMP_MSG = 2;   // 卖家回复
    const BUYER_REPLY_TMP_MSG = 3;   // 买家回复
    const PRODUCT_AUDIT_PASS_TMP_MSG = 4; // 物品审核通过通知
    const PRODUCT_AUDIT_REFUSE_TMP_MSG = 5; // 物品审核不通过通知
    const PRODUCT_AUDIT_DOWN_TMP_MSG = 6;  // 物品强制下架通知
    const BUSINESS_ORDER_FANLI_SUCCESS_TMP_MSG = 7; // 返利到账通知
    const BUSINESS_ORDER_FANLI_APPLY_TMP_MSG = 8; // 返利申请成功通知

    /**
     * 写入到模板消息推送列表
     *
     * @param       $buyerUid
     * @param       $salerUid
     * @param       $productId
     * @param       $type
     * @param array $jsonData
     *
     * @author     xudt
     * @date-time  2021/8/19 14:55
     */
    public function saveTemplateMsgRecord($buyerUid, $salerUid, $productId, $type, $jsonData = [])
    {
        $now = time();
        $data = [
            'buyer_uid' => $buyerUid,
            'saler_uid' => $salerUid,
            'product_id' => strval($productId),
            'is_read' => MessageService::UNREAD_STATUS,
            'type' => $type,
            'json_data' => json_encode($jsonData, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
        ];
        $templateMsgRecordModel = TemplateMsgRecord::find()->where(['buyer_uid' => $buyerUid, 'saler_uid' => $salerUid, 'product_id' => $productId, 'type' => $type])->one();
        if (empty($templateMsgRecordModel)) {
            $templateMsgRecordModel = new TemplateMsgRecord();
            $templateMsgRecordModel->attributes = $data;
        } else {
            $templateMsgRecordModel->is_read = MessageService::UNREAD_STATUS;
            $templateMsgRecordModel->created_at = $now;
        }
        $templateMsgRecordModel->save();
    }


    /**
     * 我想要或买家回复，未读消息发送给卖家
     *
     * @param $startTime
     * @param $endTime
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/19 15:20
     */
    public function getBuyerReplyMsgTmpList($startTime, $endTime)
    {
        $data = [];
        $salerUidArr = TemplateMsgRecord::find()->select(['saler_uid'])->where(['type' => [TemplateMsgService::WANT_PRODUCT_TMP_MSG, TemplateMsgService::BUYER_REPLY_TMP_MSG], 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->distinct(true)->column();
        if (!empty($salerUidArr)) {
            foreach ($salerUidArr as $uid) {
                $recordList = TemplateMsgRecord::find()->where(['type' => [TemplateMsgService::WANT_PRODUCT_TMP_MSG, TemplateMsgService::BUYER_REPLY_TMP_MSG], 'saler_uid' => $uid, 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->asArray()->all();
                $countUidArr = array_column($recordList, 'buyer_uid');

                // 取第一条
                $recordInfo = $recordList[0];
                $productInfo = Product::find()->select(['name', 'price'])->where(['id' => $recordInfo['product_id']])->asArray()->one();
                $userList = User::find()->select(['id', 'wx', 'wx_openid'])->where(['id' => [$recordInfo['buyer_uid'], $uid]])->indexBy('id')->asArray()->all();
                $buyerWx = $userList[$recordInfo['buyer_uid']]['wx'];
                $salerWxOpenid = $userList[$uid]['wx_openid'];
                $salerOfficialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $salerWxOpenid])->scalar();

                $data[] = [
                    'product_name' => $productInfo['name'],
                    'price' => $productInfo['price'],
                    'wx' => $buyerWx,
                    'time' => date("Y年m月d日 H:i", $recordInfo['created_at']),
                    'count' => count(array_unique($countUidArr)),
                    'official_openid' => $salerOfficialOpenid,
                    'type' => TemplateMsgService::WANT_PRODUCT_TMP_MSG,
                ];
            }
        }
        return $data;
    }

    /**
     * 卖家回复，未读消息发送给买家
     *
     * @param $startTime
     * @param $endTime
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/23 16:31
     */
    public function getSalerReplyMsgTmpList($startTime, $endTime)
    {
        $data = [];
        $buyerUidArr = TemplateMsgRecord::find()->select(['buyer_uid'])->where(['type' => TemplateMsgService::SALER_REPLY_TMP_MSG, 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->distinct(true)->column();
        if (!empty($buyerUidArr)) {
            foreach ($buyerUidArr as $uid) {
                $recordList = TemplateMsgRecord::find()->where(['type' => TemplateMsgService::SALER_REPLY_TMP_MSG, 'buyer_uid' => $uid, 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->asArray()->all();
                $countUidArr = array_column($recordList, 'saler_uid');

                // 取第一条
                $recordInfo = $recordList[0];
                $productInfo = Product::find()->select(['name', 'price'])->where(['id' => $recordInfo['product_id']])->asArray()->one();

                $userList = User::find()->select(['id', 'wx', 'wx_openid'])->where(['id' => [$recordInfo['saler_uid'], $uid]])->indexBy('id')->asArray()->all();
                $salerWx = $userList[$recordInfo['saler_uid']]['wx'];
                $buyerWxOpenid = $userList[$uid]['wx_openid'];
                $buyerOfficialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $buyerWxOpenid])->scalar();

                $data[] = [
                    'product_name' => $productInfo['name'],
                    'price' => $productInfo['price'],
                    'wx' => $salerWx,
                    'time' => date("Y年m月d日 H:i", $recordInfo['created_at']),
                    'count' => count(array_unique($countUidArr)),
                    'official_openid' => $buyerOfficialOpenid,
                    'type' => TemplateMsgService::SALER_REPLY_TMP_MSG,
                ];
            }
        }
        return $data;
    }


    /**
     * 商品审核及下架消息通知
     *
     * @param $type
     * @param $startTime
     * @param $endTime
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/27 15:40
     */
    public function getAuditMsgTmpList($type, $startTime, $endTime)
    {
        $data = [];
        $uidArr = TemplateMsgRecord::find()->select(['saler_uid'])->where(['type' => $type, 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->distinct(true)->column();
        if (!empty($uidArr)) {
            foreach ($uidArr as $uid) {
                $recordList = TemplateMsgRecord::find()->where(['type' => $type, 'saler_uid' => $uid, 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->asArray()->all();

                $recordInfo = $recordList[0];
                $productInfo = Product::find()->select(['name', 'price', 'audit_reason'])->where(['id' => $recordInfo['product_id']])->asArray()->one();

                $wxOpenid = User::find()->select(['wx_openid'])->where(['id' => $recordInfo['saler_uid']])->scalar();
                $officialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $wxOpenid])->scalar();

                $data[] = [
                    'product_name' => $productInfo['name'],
                    'price' => $productInfo['price'],
                    'audit_reason' => $productInfo['audit_reason'],
                    'time' => date("Y年m月d日 H:i", $recordInfo['created_at']),
                    'count' => count($recordList),
                    'official_openid' => $officialOpenid,
                    'type' => $type,
                ];
            }
        }
        return $data;
    }

    /**
     * 订单返利到账数据
     *
     * @param $type
     * @param $startTime
     * @param $endTime
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/15 22:49
     */
    public function getOrderFanliMsgTmpList($startTime, $endTime)
    {
        $data = [];
        $recordList = TemplateMsgRecord::find()->where(['type' => [TemplateMsgService::BUSINESS_ORDER_FANLI_SUCCESS_TMP_MSG, TemplateMsgService::BUSINESS_ORDER_FANLI_APPLY_TMP_MSG], 'is_read' => MessageService::UNREAD_STATUS])->andWhere(['>=', 'created_at', $startTime])->andWhere(['<', 'created_at', $endTime])->asArray()->all();
        if (!empty($recordList)) {
            foreach ($recordList as $key => $value) {
                $jsonData = json_decode($value['json_data'], true);

                $wxOpenid = User::find()->select(['wx_openid'])->where(['id' => $value['saler_uid']])->scalar();
                $officialOpenid = UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $wxOpenid])->scalar();

                $data[] = array_merge(
                    [
                        'type' => $value['type'],
                        'official_openid' => $officialOpenid,
                    ],
                    $jsonData
                );
            }
        }
        return $data;
    }

}