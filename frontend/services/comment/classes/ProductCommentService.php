<?php
/**
 * 商品评论服务类
 *
 * @author xudt
 * @date   : 2020/3/20 13:08
 */

namespace frontend\services\comment\classes;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\mongo\MongodbProductCommentRecord;
use common\models\mongo\MongodbProductReplyRecord;
use common\models\mongos\MongodbArticleCommentRecord;
use common\services\MessageService;
use common\services\WechatService;
use console\services\jobs\MessageJob;
use frontend\services\comment\abstracts\CommentAbstract;
use frontend\services\comment\interfaces\CommentInterface;
use frontend\services\thumb\classes\CommentThumbService;
use frontend\services\thumb\classes\ReplyThumbService;
use Yii;

class ProductCommentService extends CommentAbstract implements CommentInterface
{
    const COMMENT_AUDIT_STATUS = 1; //评论审核通过

    /** @var \rediscluster $redisBaseCluster */
    private $redisBaseCluster;

    private $redisKey;
    private $productDataRedisKey;

    private $productId;
    private $uid;


    /**
     * ProductCommentService constructor.
     *
     * @param int $uid
     * @param int $productId
     */
    public function __construct($uid, $productId)
    {
        $this->redisBaseCluster = Yii::$app->get("redisBase")->getRedisCluster();
        $this->redisKey = RedisHelper::RK('userProductData', 'comment', $uid);
        $this->productDataRedisKey = RedisHelper::RK("productData", $productId);
        $this->productId = intval($productId);
        $this->uid = intval($uid);
    }

    /**
     * 发表评论
     *
     * @param int   $authorUid
     * @param array $userInfo
     * @param array $postData
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/3/21 17:14
     *
     */
    public function comment($authorUid = 0, $userInfo = [], $postData = [])
    {
        //添加评论
        $addRes = $this->addComment($this->productId, $authorUid, $userInfo, $postData);
        if ($addRes['result']) {
            //加入到我的评论文章 zset
            $this->addUserCommentToRedis($this->redisBaseCluster, $this->redisKey, $this->productId);

            //修改商品的评论数
            $this->incrRedisData($this->redisBaseCluster, $this->productDataRedisKey, "comment_num", 1);

            //发送系统消息
            if ($authorUid != $userInfo['uid']) {
                // 给作者发系统消息
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => [
                                [
                                    'userInfo' => $userInfo,
                                    'productId' => $this->productId,
                                    'messageType' => MessageService::SYSTEM_COMMENT_MESSAGE
                                ]
                            ]
                        ]
                    )
                );
            }
        }
        return $addRes;
    }

    /**
     * 回复评论/回复
     *
     * @param int    $authorUid
     * @param string $commentId
     * @param array  $userInfo
     * @param array  $postData
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/3/21 17:44
     *
     */
    public function reply($authorUid = 0, $commentId = '', $userInfo = [], $postData = [])
    {
        //添加回复
        $addRes = $this->addReply($authorUid, $this->productId, $commentId, $userInfo, $postData);
        if ($addRes['result']) {
            //修改商品的评论数
            $this->incrRedisData($this->redisBaseCluster, $this->productDataRedisKey, "comment_num", 1);

            $messageData = [];
            if ($authorUid != $userInfo['uid']) {
                //加入到我的评论文章 zset
                $this->addUserCommentToRedis($this->redisBaseCluster, $this->redisKey, $this->productId);
                // 给作者发消息
                $messageData[] = [
                    'userInfo' => $userInfo,
                    'productId' => $this->productId,
                    'messageType' => MessageService::SYSTEM_COMMENT_MESSAGE
                ];
            }

            // 对评论的作者发消息 - 要求不能是作者、本人
            MongodbProductCommentRecord::resetTableName($this->productId);
            $commentUid = MongodbProductCommentRecord::find()->select(['uid'])->where(['comment_id' => $commentId])->scalar();
            if ($commentUid != $authorUid && $commentUid != $userInfo['uid']) { // 不是作者uid且不是评论者自身
                $messageData[] = [
                    'userInfo' => $userInfo,
                    'productId' => $this->productId,
                    'messageType' => MessageService::SYSTEM_REPLY_MESSAGE,
                    'getterUid' => $commentUid,
                ];
            }

            // 对回复作者发消息--要求不能是作者、评论的作者、自己本人
            if (!empty($postData['reply_id'])) {
                $replyUid = isset($addRes['notice_users']['uid']) ? $addRes['notice_users']['uid'] : 0;
                if ($replyUid != 0 && $replyUid != $authorUid && $replyUid != $commentUid && $replyUid != $userInfo['uid']) {
                    $messageData[] = [
                        'userInfo' => $userInfo,
                        'productId' => $this->productId,
                        'messageType' => MessageService::SYSTEM_REPLY_MESSAGE,
                        'getterUid' => $replyUid,
                    ];
                }
            }

            // 推送到队列发送消息
            if (!empty($messageData)) {
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => $messageData
                        ]
                    )
                );
            }
        }
        return $addRes;
    }


    /**
     * 获取商品评论列表
     *
     * @param int $page
     * @param int $pageSize
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/3/9 14:40
     */
    public function getCommentList($page = 1, $pageSize = 20)
    {
        //获取评论
        $commentList = $this->getCommentListByProductId($this->productId, $page, $pageSize);
        if (!empty($commentList)) {
            foreach ($commentList as $key => &$value) {
                $value['avatar'] = ToolsHelper::getLocalImg($value['avatar'], Yii::$app->params['defaultAvatar'], 240);
                $value['publish_date'] = ToolsHelper::getTimeStrDiffNow($value['created_at']);
                $value['reply_list'] = $this->getReplyList($value['comment_id']);
                unset($value['_id'], $value['status'], $value['product_id'], $value['created_at']);
            }
        }
        return $commentList;
    }

    /**
     * 添加评论
     *
     * @param int   $productId
     * @param int   $authorUid
     * @param array $userInfo
     * @param array $postData
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/3/21 17:14
     *
     */
    public function addComment($productId, $authorUid = 0, $userInfo = [], $postData = [])
    {
        //内容安全检测
        $wechatService = new WechatService();
        $checkResult = $wechatService->msgSecCheck($postData['content']);
        if (!$checkResult) {
            return ToolsHelper::funcReturn("内容涉嫌违规");
        }

        MongodbProductCommentRecord::resetTableName($productId);
        $model = new MongodbProductCommentRecord();
        $model->product_id = $productId;
        $model->comment_id = ToolsHelper::getUniqidKey("comment");
        $model->uid = intval($userInfo['uid']);
        $model->nickname = $userInfo['nickname'];
        $model->avatar = ToolsHelper::getLocalImg($userInfo['avatar'], Yii::$app->params['defaultAvatar'], 240);
        $model->content = $postData['content'];
        $model->author = $authorUid == $userInfo['uid'] ? 1 : 0;
        $model->status = self::COMMENT_AUDIT_STATUS; //1 已审核
        $model->created_at = time();
        $res = $model->save();
        if (!$res) {
            return ToolsHelper::funcReturn("留言失败");
        }
        $commentData = array_merge($model->attributes, ['publish_date' => ToolsHelper::getTimeStrDiffNow($model->created_at), 'reply_list' => []]);
        unset($commentData['_id'], $commentData['product_id'], $commentData['status'], $commentData['created_at']);

        return ToolsHelper::funcReturn(
            "留言成功",
            true,
            $commentData
        );
    }


    /**
     * 添加回复
     *
     * @param $authorUid
     * @param $productId
     * @param $commentId
     * @param $userInfo
     * @param $postData
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 14:16
     */
    public function addReply($authorUid, $productId, $commentId, $userInfo, $postData)
    {
        //内容安全检测
        $wechatService = new WechatService();
        $checkResult = $wechatService->msgSecCheck($postData['content']);
        if (!$checkResult) {
            return ToolsHelper::funcReturn("内容涉嫌违规");
        }

        $commentInfo = [];
        if (!empty($postData['reply_id'])) { //回复-回复
            //查询回复的作者及姓名
            MongodbProductReplyRecord::resetTableName($commentId);
            $commentInfo = MongodbProductReplyRecord::find()->select(['uid', 'nickname'])->where(['reply_id' => $postData['reply_id']])->asArray()->one();
            unset($commentInfo['_id']);
        }
        MongodbProductReplyRecord::resetTableName($commentId);
        $model = new MongodbProductReplyRecord();
        $model->product_id = $productId;
        $model->comment_id = $commentId;
        $model->reply_id = ToolsHelper::getUniqidKey("reply");
        $model->uid = intval($userInfo['uid']);
        $model->nickname = $userInfo['nickname'];
        $model->avatar = ToolsHelper::getLocalImg($userInfo['avatar'], Yii::$app->params['defaultAvatar'], 240);
        $model->notice_users = json_encode($commentInfo, 512);
        $model->content = $postData['content'];
        $model->author = $authorUid == $userInfo['uid'] ? 1 : 0;
        $model->status = self::COMMENT_AUDIT_STATUS;
        $model->created_at = time();
        $res = $model->save();
        if (!$res) {
            return ToolsHelper::funcReturn("留言失败");
        }
        $replyData = array_merge(
            $model->attributes,
            [
                'publish_date' => ToolsHelper::getTimeStrDiffNow($model->created_at),
                'notice_users' => $commentInfo
            ]
        );
        unset($replyData['_id'], $replyData['product_id'], $replyData['status'], $replyData['created_at']);

        return ToolsHelper::funcReturn(
            "留言成功",
            true,
            $replyData
        );
    }


    /**
     * 获取评论的回复列表
     *
     * @param string $commentId
     *
     * @return array|\yii\mongodb\ActiveRecord
     * @author   xudt
     * @dateTime 2020/3/20 21:12
     *
     */
    public function getReplyList($commentId = '')
    {
        //获取回复
        $replyList = $this->getReplyListByCommentKey($commentId);
        if (!empty($replyList)) {
            foreach ($replyList as $key => &$value) {
                $value['avatar'] = ToolsHelper::getLocalImg($value['avatar'], Yii::$app->params['defaultAvatar'], 240);
                $value['publish_date'] = ToolsHelper::getTimeStrDiffNow($value['created_at']);
                $value['notice_users'] = json_decode($value['notice_users'], 512);
                unset($value['_id'], $value['status'], $value['product_id'], $value['created_at']);
            }
        }
        return $replyList;
    }

    /**
     * 获取文章评论列表
     *
     * @param int $productId
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\mongodb\ActiveRecord
     * @author   xudt
     * @dateTime 2020/3/20 20:42
     *
     */
    private function getCommentListByProductId($productId, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        MongodbProductCommentRecord::resetTableName($productId);
        return MongodbProductCommentRecord::find()->where(['product_id' => $productId])->andWhere(['status' => self::COMMENT_AUDIT_STATUS])->orderBy("created_at desc")->offset($start)->limit($pageSize)->asArray()->all();
    }


    /**
     * 获取评论的回复列表
     *
     * @param $commentId
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/3/9 14:45
     */
    private function getReplyListByCommentKey($commentId)
    {
        MongodbProductReplyRecord::resetTableName($commentId);
        return MongodbProductReplyRecord::find()->where(['comment_id' => $commentId])->andWhere(['status' => self::COMMENT_AUDIT_STATUS])->orderBy("created_at asc")->asArray()->all();
    }
}