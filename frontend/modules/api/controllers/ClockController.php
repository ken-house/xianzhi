<?php
/**
 * 网红打卡地
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/7/16 16:34
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\ClockService;
use common\services\MessageService;
use common\services\ProductService;
use common\services\RewardPointService;
use console\services\jobs\MessageJob;
use Yii;

class ClockController extends BaseController
{
    const PAGESIZE = 20;
    /**
     * 表单默认值
     *
     * @var array
     */
    private $clockInfo = [
        'id' => 0,
        'name' => '',
        'title' => '',
        'info' => '',
        'pics' => [],
        'tags' => [],
        'price' => '0.00',
        'location' => '',
        'lat' => 0,
        'lng' => 0,
    ];

    /**
     * 打卡列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/16 19:59
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $params['keyword'] = Yii::$app->request->get('keyword', '');
        $params['lat'] = Yii::$app->request->get('lat', 0);
        $params['lng'] = Yii::$app->request->get('lng', 0);

        $clockService = new ClockService();
        $clockList = $clockService->getClockListByTuijain($uid, $params, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "打卡首页",
            true,
            [
                'userInfo' => $userInfo,
                'clockList' => array_values($clockList),
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 我的打卡列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/21 20:35
     */
    public function actionVisit()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $uid = Yii::$app->request->get('uid', 0); //访问的用户uid
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $productService = new ProductService();
        $authorData = $productService->getUserStatisticData($uid);

        $clockService = new ClockService();
        $clockList = $clockService->getUserClockList($authorData, $page, $pageSize);
        return ToolsHelper::funcReturn(
            "我的打卡列表",
            true,
            [
                'visitUserInfo' => $userInfo,
                'clockList' => $clockList,
                'authorData' => $authorData,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 发布页面
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/6 09:41
     */
    public function actionPublish()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $clockId = Yii::$app->request->get('clock_id', 0); // 打卡ID

        $clockInfo = $this->clockInfo;
        if ($clockId != 0) {
            $clockService = new ClockService();
            $result = $clockService->getClockInfoFromDb($userInfo['uid'], $clockId);
            if (!$result['result']) {
                return $result;
            }
            $clockInfo = $result['data']['clockInfo'];
        }

        return ToolsHelper::funcReturn(
            "打卡发布",
            true,
            [
                'userInfo' => $userInfo,
                'clockInfo' => $clockInfo,
            ]
        );
    }

    /**
     * 保存打卡到数据库中
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function actionSave()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $clockInfo = Yii::$app->request->post(); // 打卡详情

        $clockService = new ClockService();
        return $clockService->saveClockInfoToDb($userInfo, $clockInfo);
    }


    /**
     * 打卡详情
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

        $clockId = Yii::$app->request->get('id', 0);
        $from = Yii::$app->request->get('from', ''); // from=mycenter时，查询数据库，其他情况查询ES
        $now = time();

        $clockService = new ClockService();
        $clockInfo = $clockService->getClockPageData($clockId, $uid, $from);
        if (empty($clockInfo)) {
            return ToolsHelper::funcReturn("请在后台关闭微信，重新进入小程序");
        }

        // 猜您喜欢
        $guessClockList = $clockService->getGuessLoveClockList($clockId, 20);

        // 导航
        $versionNum = Yii::$app->request->headers->get('version-num');
        $navList = ToolsHelper::getNavListByPageType(2, ['version_num' => $versionNum]);

        $data = [
            'userInfo' => $userInfo,
            'clockInfo' => $clockInfo,
            'navList' => $navList,
            'guessClockList' => array_values($guessClockList),
            'showButton' => ToolsHelper::showButton($versionNum),
        ];

        //增加用户浏览数据及商品浏览次数
        if ($clockInfo['status'] == ClockService::STAUTS_PASS && $uid != $clockInfo['uid']) {
            $currentViewNum = $clockService->incrViewClockData($clockId);

            if ($currentViewNum && $uid != 0) {
                // 若未完成今日浏览，则进行增加积分
                $rewardPointService = new RewardPointService(RewardPointService::VIEW_AWARD_TYPE, $uid, $now);
                $rewardRes = $rewardPointService->onceTaskAwardPoint();
                if ($rewardRes['result']) {
                    $data['rewardTips'] = $rewardRes['message'];
                }
            }

            // 浏览次数达到奖励增加作者积分
            if ($currentViewNum % 100 == 0 && $currentViewNum <= 500) {
                $rewardPointService = new RewardPointService(RewardPointService::CLOCK_VIEW_AWARD_TYPE, $clockInfo['uid'], $now);
                $awardRes = $rewardPointService->awardPoint();
                if ($awardRes['result']) {
                    // 发送系统消息
                    Yii::$app->messageQueue->push(
                        new MessageJob(
                            [
                                'data' => [
                                    [
                                        'userInfo' => [
                                            'uid' => MessageService::SYSTEM_USER
                                        ],
                                        'clockId' => $clockInfo['id'],
                                        'messageType' => MessageService::SYSTEM_CLOCK_VIEW_AWARD_MESSAGE,
                                        'rewardPoint' => $awardRes['data']['point'],
                                        'viewNum' => $currentViewNum,
                                    ]
                                ]
                            ]
                        )
                    );
                }
            }
        }

        return ToolsHelper::funcReturn('商品详情', true, $data);
    }

    /**
     * 我的打卡列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/16 20:23
     */
    public function actionMy()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $clockService = new ClockService();
        $clockList = $clockService->getClockList($uid, $page, $pageSize);
        return ToolsHelper::funcReturn(
            "商品列表",
            true,
            [
                'userInfo' => $userInfo,
                'clockList' => $clockList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }


    /**
     * 删除打卡
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:50
     */
    public function actionDelete()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $clockId = Yii::$app->request->post('id', 0);

        $clockService = new ClockService();
        return $clockService->deleteClock($userInfo, $clockId);
    }

    /**
     * 推荐或不推荐
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/29 11:40
     */
    public function actionTuijian()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $clockId = Yii::$app->request->post('id', 0);
        $tuijian = Yii::$app->request->post('tuijian', 0); // 1 推荐 2 不推荐

        $clockService = new ClockService();
        return $clockService->tuijianClock($userInfo, $clockId, $tuijian);
    }
}