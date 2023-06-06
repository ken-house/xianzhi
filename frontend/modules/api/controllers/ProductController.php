<?php
/**
 * 商品
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/1 16:40
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\models\elasticsearch\EsProduct;
use common\services\BannerService;
use common\services\BusinessProductService;
use common\services\JdService;
use common\services\MessageService;
use common\services\PddService;
use common\services\ProductService;
use common\services\RewardPointService;
use common\services\TemplateMsgService;
use common\services\ThumbService;
use common\services\UnionService;
use common\services\UserService;
use console\services\jobs\MessageJob;
use frontend\services\comment\classes\ProductCommentService;
use Yii;

class ProductController extends BaseController
{
    const PAGESIZE = 10;
    /**
     * 商品信息默认值
     *
     * @var array
     */
    private $productInfo = [
        'id' => 0,
        'name' => '',
        'title' => '',
        'info' => '',
        'pics' => [],
        'tags' => [],
        'price' => '0.00',
        'original_price' => '0.00',
        'location' => '',
        'lat' => 0,
        'lng' => 0,
    ];

    /**
     * 商品发布页面
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 09:41
     */
    public function actionPublish()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productId = Yii::$app->request->get('product_id', 0); // 商品Id

        //获取商品信息
        $productInfo = $this->productInfo;
        if ($productId != 0) {
            $productService = new ProductService();
            $result = $productService->getProductInfoFromDb($userInfo['uid'], $productId);
            if (!$result['result']) {
                return $result;
            }
            $productInfo = $result['data']['productInfo'];
        }

        // 查询用户地址
        $userService = new UserService();
        $addressList = $userService->getAddressList($userInfo['uid']);

        // 是否关注公众号
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);

        return ToolsHelper::funcReturn(
            "商品发布",
            true,
            [
                'userInfo' => $userInfo,
                'tagList' => Yii::$app->params['productTags'],
                'productInfo' => $productInfo,
                'addressList' => $addressList,
                'subscribe' => $subscribe,
            ]
        );
    }

    /**
     * 保存商品信息到数据库中
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function actionSave()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productInfo = Yii::$app->request->post(); // 商品详情

        $productService = new ProductService();
        return $productService->saveProductInfoToDb($userInfo, $productInfo);
    }

    /**
     * 刷新商品的更新时间
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 13:26
     */
    public function actionRefresh()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productId = Yii::$app->request->post('id', 0);

        $now = time();

        $productService = new ProductService();
        $res = $productService->refreshProduct($userInfo, $productId);

        if ($res['result']) {
            // 积分消耗
            $rewardPointService = new RewardPointService(RewardPointService::REFRESH_AWARD_TYPE, $userInfo['uid'], $now);
            $rewardRes = $rewardPointService->awardPoint();
            if (!$rewardRes['result']) {
                return $res;
            }
            $res['data']['rewardTips'] = "擦亮成功，已消耗" . abs($rewardRes['data']['point']) . "积分";
        }

        return $res;
    }

    /**
     * 更新商品状态
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:50
     */
    public function actionStatus()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productId = Yii::$app->request->post('id', 0);
        $opType = Yii::$app->request->post('op_type', '');

        $productService = new ProductService();
        return $productService->statusProduct($userInfo, $productId, $opType);
    }

    /**
     * 移除用户想要商品列表或移除下架商品
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/2/28 19:33
     */
    public function actionActiveProductRemove()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $productId = Yii::$app->request->post('id', 0);
        $type = Yii::$app->request->post('type', '');

        $productService = new ProductService();
        return $productService->activeProductRemove($uid, $productId, $type);
    }

    /**
     * 商品详情
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/9 10:47
     */
    public function actionInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        // 会员等级
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);

        $productId = Yii::$app->request->get('id', 0);
        $from = Yii::$app->request->get('from', ''); // from=mycenter时，查询数据库，其他情况查询ES
        $now = time();

        $productService = new ProductService();
        $productInfo = $productService->getProductPageData($productId, $uid, $from);
        if (empty($productInfo)) {
            return ToolsHelper::funcReturn("请在后台关闭微信，重新进入小程序");
        }

        // 导航
        $versionNum = Yii::$app->request->headers->get('version-num');
        $navList = ToolsHelper::getNavListByPageType(1, ['version_num' => $versionNum]);

        $data = [
            'userInfo' => $userInfo,
            'productInfo' => $productInfo,
            'navList' => $navList,
            'showButton' => ToolsHelper::showButton($versionNum),
        ];

        // 增加用户浏览数据及商品浏览次数
        if ($productInfo['status'] == ProductService::STAUTS_PASS) {
            $res = $productService->incrViewProductData($productId, $uid);

            if ($res && $uid != 0) {
                // 若未完成今日浏览，则进行增加积分
                $rewardPointService = new RewardPointService(RewardPointService::VIEW_AWARD_TYPE, $uid, $now);
                $rewardRes = $rewardPointService->onceTaskAwardPoint();
                if ($rewardRes['result']) {
                    $data['rewardTips'] = $rewardRes['message'];
                }
            }
        }

        // 订单数奖励情况
        $data['orderNumAwardArr'] = ToolsHelper::getOrderNumAward($uid);

        return ToolsHelper::funcReturn('商品详情', true, $data);
    }

    /**
     * 商品点赞/取消点赞
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 11:15
     */
    public function actionThumb()
    {
        $now = time();
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $productId = Yii::$app->request->post('id', 0);
        $action = Yii::$app->request->post('action', 'add'); // add 点赞 cancel 取消点赞

        $thumbService = new ThumbService($productId, $uid);
        if ($action == 'cancel') {
            return $thumbService->cancelThumbProduct();
        } else {
            $thumbRes = $thumbService->thumbProduct();
            if ($thumbRes['result']) {
                // 发送系统消息
                Yii::$app->messageQueue->push(
                    new MessageJob(
                        [
                            'data' => [
                                [
                                    'userInfo' => $userInfo,
                                    'productId' => $productId,
                                    'messageType' => MessageService::SYSTEM_THUMB_MESSAGE
                                ]
                            ]
                        ]
                    )
                );

                // 若未完成今日点赞，则进行增加积分
                $rewardPointService = new RewardPointService(RewardPointService::THUMB_AWARD_TYPE, $uid, $now);
                $rewardRes = $rewardPointService->onceTaskAwardPoint();
                if ($rewardRes['result']) {
                    $thumbRes['data']['rewardTips'] = $rewardRes['message'];
                }
            }
            return $thumbRes;
        }
    }


    /**
     * 评论列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 14:54
     */
    public function actionCommentList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $productId = Yii::$app->request->get('id', 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $productCommentService = new ProductCommentService($uid, $productId);
        $commentList = $productCommentService->getCommentList($page, $pageSize);
        return ToolsHelper::funcReturn(
            "评论列表",
            true,
            [
                'commentList' => $commentList,
                'page' => $page,
                'pageSize' => $pageSize
            ]
        );
    }

    /**
     * 添加评论/添加回复
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/9 16:10
     */
    public function actionComment()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $postData = Yii::$app->request->post();
        $productId = $postData['id'];
        $commentId = !empty($postData['comment_id']) ? $postData['comment_id'] : '';

        if (empty(trim($postData['content']))) {
            return ToolsHelper::funcReturn('内容不能为空');
        }
        $now = time();

        //查询商品卖家uid
        $productService = new ProductService();
        $productInfo = $productService->getProductInfo($productId);

        $productCommentService = new ProductCommentService($uid, $productId);
        if (empty($commentId)) {
            $res = $productCommentService->comment($productInfo['uid'], $userInfo, $postData);
        } else {
            $res = $productCommentService->reply($productInfo['uid'], $commentId, $userInfo, $postData);
        }
        if ($res['result']) {
            // 若未完成今日留言，则进行增加积分
            $rewardPointService = new RewardPointService(RewardPointService::COMMENT_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->onceTaskAwardPoint();
            if ($rewardRes['result']) {
                $res['data']['rewardTips'] = $rewardRes['message'];
                return $res;
            }
        }
        return $res;
    }

    /**
     * 我想要
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/14 19:22
     */
    public function actionWant()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);

        $userService = new UserService();
        if ($userService->denyUserWant($uid)) {
            return ToolsHelper::funcReturn("您想要的太多了，明天再来吧");
        }

        $productId = Yii::$app->request->post('id');
        if (empty($productId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productInfo = EsProduct::get($productId);
        $message = '这个宝贝我很想要，可以聊一聊吗？';

        if ($uid == $productInfo['uid']) {
            return ToolsHelper::funcReturn("主人，您不可操作哦");
        }
        $now = time();

        $productService = new ProductService();

        // 免费商品且会员等级为LV0，需要扣除100积分，每件商品每人仅扣除一次
        if (!$productService->isWantFreeAward($uid, $productId)) {
            if ($productInfo['price'] <= 1 && $userInfo['level'] == 0) {
                $rewardPointService = new RewardPointService(RewardPointService::WANT_FREE_AWARD_TYPE, $userInfo['uid'], $now);
                $rewardRes = $rewardPointService->awardPoint();
                if (!$rewardRes['result']) {
                    return $rewardRes;
                }
                $productService->setWantFreeAward($uid, $productId);
            }
        }

        // 更改数据
        $productService->incrWantProductData($productId, $uid);

        // 记录登录用户每天想要的对应的用户uid - 防止微信泄露
        $userService->recordUserWantUid($uid, $productInfo['uid']);

        // 记录到模板消息推送列表中
        $templateMsgService = new TemplateMsgService();
        $templateMsgService->saveTemplateMsgRecord($uid, $productInfo['uid'], $productId, TemplateMsgService::WANT_PRODUCT_TMP_MSG);


        // 发送系统消息
        $messageService = new MessageService($uid, $productId);
        $res = $messageService->send($uid, $productInfo['uid'], ['content' => $message]);

        if ($res['result']) {
            // 若未完成今日想要，则进行增加积分
            $rewardPointService = new RewardPointService(RewardPointService::WANT_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->onceTaskAwardPoint();
            if ($rewardRes['result']) {
                $res['data']['rewardTips'] = $rewardRes['message'];
            }
        }
        return $res;
    }


    /**
     * 分享后回调方法增加积分
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/3 18:07
     */
    public function actionShare()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $now = time();

        if ($uid != 0) {
            // 若未完成今日想要，则进行增加积分
            $rewardPointService = new RewardPointService(RewardPointService::SHARE_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->onceTaskAwardPoint();
            if ($rewardRes['result']) {
                $rewardRes['data']['rewardTips'] = $rewardRes['message'];
                return $rewardRes;
            }
        }

        return ToolsHelper::funcReturn("分享成功", true);
    }

    /**
     * 电商购物
     *
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/10 20:16
     */
    public function actionBusiness()
    {
        $now = time();
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $sourceId = Yii::$app->request->get('source_id', 1); // 1 京东 2 拼多多
        if (!in_array($sourceId, [1, 2])) {
            $sourceId = JdService::SOURCE_ID;
        }
        $keyword = Yii::$app->request->get('keyword', '');
        $channelType = Yii::$app->request->get('channel_type', 0); // 精选频道
        $page = Yii::$app->request->get('page', 1);
        $pageSize = $sourceId == 1 ? 30 : 50;

        // 记录用户搜索的关键词到用户喜好
        $userService = new UserService();
        $userService->saveUserFavourite($uid, $keyword);

        // banner列表
        $bannerService = new BannerService();
        $type = $sourceId == 1 ? 5 : 6;
        $bannerList = $bannerService->getBannerList(0, 0, $type);

        $businessProductService = new BusinessProductService();
        if (empty($keyword)) {
            if (empty($channelType)) {
                $chosenData = ToolsHelper::getBusinessProductChannelType($sourceId);
                $channelType = $chosenData['channel_type'];
            }
            $productList = $businessProductService->getBusinessProductByRecommend($sourceId, $page, $pageSize, $channelType);
        } else {
            $productList = $businessProductService->getBusinessProductByKeyword($sourceId, $keyword, $page, $pageSize);
        }

        // 搜索奖励积分，若未完成，则进行增加积分
        $rewardTips = "";
        if (!empty($uid) && !empty($keyword)) {
            $rewardPointService = new RewardPointService(RewardPointService::SEARCH_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->onceTaskAwardPoint();
            if ($rewardRes['result']) {
                $rewardTips = $rewardRes['message'];
            }
        }

        // 判断浏览60秒任务是否完成
        $isFinishViewTaskOver = 2;
        if (!empty($uid)) {
            $rewardPointService = new RewardPointService(RewardPointService::BUSINESS_VIEW_SECONDS_AWARD_TYPE, $uid, $now);
            $isFinishViewTaskOver = $rewardPointService->isFinishOnceReward();
        }


        return ToolsHelper::funcReturn(
            "电商购物",
            true,
            [
                'userInfo' => $userInfo,
                'productList' => $productList,
                'page' => $page,
                'pageSize' => $pageSize,
                'keyword' => $keyword,
                'rewardTips' => $rewardTips,
                'bannerList' => $bannerList,
                'isFinishViewTaskOver' => $isFinishViewTaskOver,
            ]
        );
    }

    /**
     * 电商推荐
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/10 20:20
     */
    public function actionBusinessRecommend()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $keyword = Yii::$app->request->get('keyword', '');
        if ($keyword == "undefined") {
            $keyword = '';
        }
        if (!empty($uid) && empty($keyword) && rand(0, 100) >= 20) { // 登录用户读取用户搜索的关键词
            $keyword = (new UserService())->getUserBestFavourite($uid);
        }
        $businessProductService = new BusinessProductService();
        if (!empty($keyword)) {
            $page = rand(1, 2);
            // 京东商品
            $jdProductList = $businessProductService->getBusinessProductByKeyword(1, $keyword, $page, 30);
            // 拼多多商品
            $pddProductList = $businessProductService->getBusinessProductByKeyword(2, $keyword, $page, 50);
        } else {
            $page = rand(1, 3);
            // 京东商品
            $jdChosenData = ToolsHelper::getBusinessProductChannelType(JdService::SOURCE_ID);
            $jdProductList = $businessProductService->getBusinessProductByRecommend(1, $page, 50, $jdChosenData['channel_type']);

            // 拼多多商品
            $pddChosenData = ToolsHelper::getBusinessProductChannelType(PddService::SOURCE_ID);
            $pddProductList = $businessProductService->getBusinessProductByRecommend(2, $page, 50, $pddChosenData['channel_type']);
        }
        $businessProductList = array_merge($jdProductList, $pddProductList);

        shuffle($businessProductList);

        return ToolsHelper::funcReturn(
            "电商推荐",
            true,
            [
                'businessProductList' => array_values($businessProductList),
            ]
        );
    }
}