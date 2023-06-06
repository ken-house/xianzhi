<?php
/**
 * 首页
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/1/7 10:24
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;

use common\services\BannerService;
use common\services\BusinessProductService;
use common\services\ProductService;
use common\services\RewardPointService;
use common\services\SearchKeywordService;
use common\services\UserService;
use Yii;
use yii\helpers\ArrayHelper;

class IndexController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/1 16:46
     */
    public function actionIndex()
    {
        $now = time();
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);

        $params['type'] = Yii::$app->request->get('type', 0);  // 0 首页 1 热门专区 2 免费专区 3 宠物领养  4 房产出租
        $params['keyword'] = Yii::$app->request->get('keyword', '');
        $params['dist_type'] = Yii::$app->request->get('dist_type', 0);
        $params['lat'] = Yii::$app->request->get('lat', 0);
        $params['lng'] = Yii::$app->request->get('lng', 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $versionNum = Yii::$app->request->headers->get('version-num');

        // 记录用户搜索的关键词到用户喜好
        $userService = new UserService();
        $userService->saveUserFavourite($uid, $params['keyword']);

        $productService = new ProductService();
        $productList = $productService->getIndexProductList($uid, $params, $page, $pageSize);

        $navList = ToolsHelper::getNavListByPageType(0, ['version_num' => $versionNum]);

        // banner列表
        $bannerService = new BannerService();
        $bannerList = $bannerService->getBannerList($params['lat'], $params['lng'], $params['type']);

        // 公告设置
        $noticeSetting = Yii::$app->params['noticeSetting'];
        if ($versionNum != Yii::$app->params['auditVersionNum']) {
            $noticeSetting['show'] = 0;
        }


        $pageName = "小区闲置物品";
        $emptyText = "附近还没有闲置物品，看看推荐吧";
        $distTypeList = ToolsHelper::getDistTypeList();
        if (!empty($params['type'])) {
            switch ($params['type']) {
                case 1:
                    $pageName = "热门专区";
                    $emptyText = "附近还没有热门闲置物品，看看推荐吧";
                    $distTypeList = ToolsHelper::getDistTypeList();
                    break;
                case 2:
                    $pageName = "免费专区";
                    $emptyText = "附近还没有免费闲置物品，看看推荐吧";
                    $distTypeList = ToolsHelper::getDistTypeList();
                    break;
                case 3:
                    $pageName = "宠物领养";
                    $emptyText = "附近还没有可领养的宠物，看看推荐吧";
                    $distTypeList = ToolsHelper::getDistTypeList(1);
                    break;
                case 4:
                    $pageName = "房产出租";
                    $emptyText = "附近还没有房产出租，看看推荐吧";
                    $distTypeList = ToolsHelper::getDistTypeList(1);
                    break;
            }
        }

        // 搜索奖励积分，若未完成，则进行增加积分
        $rewardTips = "";
        if (!empty($uid) && !empty($params['keyword'])) {
            $rewardPointService = new RewardPointService(RewardPointService::SEARCH_AWARD_TYPE, $uid, $now);
            $rewardRes = $rewardPointService->onceTaskAwardPoint();
            if ($rewardRes['result']) {
                $rewardTips = $rewardRes['message'];
            }
        }

        return ToolsHelper::funcReturn(
            "首页",
            true,
            [
                'userInfo' => $userInfo,
                'bannerList' => $bannerList,
                'navList' => $navList,
                'productList' => array_values($productList),
                'page' => $page,
                'pageSize' => $pageSize,
                'distTypeList' => $distTypeList,
                'showButton' => ToolsHelper::showButton($versionNum),
                'noticeSetting' => $noticeSetting,
                'pageName' => $pageName,
                'emptyText' => $emptyText,
                'rewardTips' => $rewardTips,
            ]
        );
    }

    /**
     * 跑马灯滚动数据
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/11 12:11
     */
    public function actionNotice()
    {
        $noticeList = RewardPointService::getUserAwardNotice();
        return ToolsHelper::funcReturn(
            "跑马灯滚动数据",
            true,
            [
                'noticeList' => $noticeList
            ]
        );
    }
}