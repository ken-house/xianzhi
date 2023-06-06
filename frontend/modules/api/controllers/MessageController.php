<?php
/**
 * 消息
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/1 16:40
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;

use common\models\Product;
use common\services\MessageService;
use common\services\ProductService;
use common\services\TemplateMsgService;
use common\services\UnionService;
use common\services\UserService;
use common\services\WechatService;
use Yii;

class MessageController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 小红点，未读消息数
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/14 16:30
     */
    public function actionRedPoint()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        if ($uid == 0) {
            return ToolsHelper::funcReturn('小红点');
        }

        //读取消息列表
        $messageService = new MessageService($uid);
        $totalUnreadNum = $messageService->getUnreadNum();
        return ToolsHelper::funcReturn('小红点', true, ['totalUnreadNum' => $totalUnreadNum]);
    }

    /**
     * 消息首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/15 15:59
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        //读取消息列表
        $messageService = new MessageService($uid);
        $messageList = $messageService->getList($page, $pageSize);

        // 系统消息
        $systemMessageData = $messageService->getLatestSystemMessageData();

        return ToolsHelper::funcReturn(
            "消息列表",
            true,
            [
                'messageList' => $messageList,
                'systemMessageData' => $systemMessageData,
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }


    /**
     * 消息详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/10 21:41
     */
    public function actionDetail()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $messageKey = Yii::$app->request->get('message_key');
        $messageKeyArr = explode('-', trim($messageKey, '-'));
        $productId = intval($messageKeyArr[2]); // 商品id
        $otherUid = $uid == $messageKeyArr[0] ? intval($messageKeyArr[1]) : intval($messageKeyArr[0]); // 发送着或接收者

        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        //读取消息列表
        $messageService = new MessageService($uid, $productId);
        $messageResult = $messageService->getDetailList($otherUid, $page, $pageSize);

        // 商品信息
        $productService = new ProductService();
        $productInfo = $productService->getProductInfo($productId);

        // 买家或买家的用户信息
        $userService = new UserService();
        $otherUserInfo = $userService->getUserAllDataFromRedisMysql($otherUid);

        // 是否关注公众号
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);
        $otherSubScribe = $unionService->isSubscribe($otherUserInfo['wx_openid']);

        //全部设置已读
        $buyerUid = $productInfo['uid'] == $uid ? $otherUid : $uid;
        $messageService->readMessage($messageKey, $otherUid, $buyerUid, $productInfo['uid']);

        return ToolsHelper::funcReturn(
            "消息列表",
            true,
            [
                'userInfo' => $userInfo,
                'subscribe' => $subscribe,
                'otherUserInfo' => [
                    'uid' => $otherUserInfo['uid'],
                    'wx' => $otherUserInfo['wx'],
                    'wx_public' => !empty($otherUserInfo['wx_public']) ? 1 : 0,
                    'subscribe' => $otherSubScribe,
                ],
                'productInfo' => $productInfo,
                'messageList' => $messageResult['messageList'],
                'lastestMessageId' => $messageResult['lastestMessageId'],
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }

    /**
     * 消息发送
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/10 14:18
     */
    public function actionSend()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $messageKey = Yii::$app->request->post('message_key');
        $messageKeyArr = explode('-', trim($messageKey, '-'));
        $productId = intval($messageKeyArr[2]); // 商品id
        $getterUid = $uid == $messageKeyArr[0] ? intval($messageKeyArr[1]) : intval($messageKeyArr[0]); // 接收者
        $content = Yii::$app->request->post('content', '');
        if (empty(trim($content))) {
            return ToolsHelper::funcReturn("消息内容不能为空");
        }

        //内容安全检测
        $wechatService = new WechatService();
        $checkResult = $wechatService->msgSecCheck($content);
        if (!$checkResult) {
            return ToolsHelper::funcReturn("内容涉嫌违规");
        }

        $messageService = new MessageService($uid, $productId);
        $sendRes = $messageService->send($uid, $getterUid, ['content' => $content]);
        // 卖家或买家消息回复记录到模板消息推送列表中
        if ($sendRes['result']) {
            $productUid = Product::find()->select(['uid'])->where(['id' => $productId])->scalar();
            $templateMsgService = new TemplateMsgService();
            if ($uid == $productUid) { // 卖家回复
                $templateMsgService->saveTemplateMsgRecord($getterUid, $uid, $productId, TemplateMsgService::SALER_REPLY_TMP_MSG);
            } else { // 买家回复
                $templateMsgService->saveTemplateMsgRecord($uid, $getterUid, $productId, TemplateMsgService::BUYER_REPLY_TMP_MSG);
            }
        }
        return $sendRes;
    }

    /**
     * 删除消息
     *
     * @return array
     * @throws \yii\db\StaleObjectException
     *
     * @author     xudt
     * @date-time  2021/3/11 10:17
     */
    public function actionDelete()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $messageKey = Yii::$app->request->post('message_key');
        $messageKeyArr = explode('-', trim($messageKey, '-'));
        $productId = intval($messageKeyArr[2]); // 商品id
        $otherUid = $uid == $messageKeyArr[0] ? intval($messageKeyArr[1]) : intval($messageKeyArr[0]); // 发送着或接收者

        $messageService = new MessageService($uid, $productId);
        return $messageService->delete($messageKey, $otherUid);
    }

    /**
     * 系统消息通知列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/16 21:49
     */
    public function actionSystemMessageList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        //读取消息列表
        $messageService = new MessageService($uid);
        $messageResult = $messageService->getSystemMessageList($page, $pageSize);

        //全部设置已读
        $messageKey = $messageService->getMessageKey(MessageService::SYSTEM_USER, $uid);
        $messageService->readMessage($messageKey, MessageService::SYSTEM_USER);

        return ToolsHelper::funcReturn(
            "系统消息列表",
            true,
            [
                'messageList' => $messageResult['messageList'],
                'lastestMessageId' => $messageResult['lastestMessageId'],
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }
}