<?php
/**
 * 我的页面
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/1 15:53
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BusinessProductService;
use common\services\FanliService;
use common\services\ParttimeJobService;
use common\services\ProductService;
use common\services\UnionService;
use common\services\UserService;
use Yii;

class MycenterController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 我的页面首页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/1 16:18
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $productService = new ProductService();

        $now = time();

        //用户卖出、浏览、点赞、评论、想要商品
        $dataArr['view_num'] = $productService->getUserActiveData($uid, 'view');
        $dataArr['thumb_num'] = $productService->getUserActiveData($uid, 'thumb');
        $dataArr['comment_num'] = $productService->getUserActiveData($uid, 'comment');
        $dataArr['want_num'] = $productService->getUserActiveData($uid, 'want');
        $dataArr['sale_num'] = $productService->getUserActiveData($uid, 'sale');

        // 我发布的数量
        $dataArr['publish_num'] = $productService->getProductNum($uid);
        $dataArr['jianzhi_num'] = (new ParttimeJobService())->getMyJobCount($uid);

        // 我的购物
        $fanliService = new FanliService();
        $shopData = $fanliService->getStatisticData($uid);

        // 加入购物车、浏览记录数
        $shopData['shop_car_num'] = BusinessProductService::getUserBehaviorData($uid,BusinessProductService::SHOPCAR_RECORD_TYPE);
        $shopData['shop_view_num'] = BusinessProductService::getUserBehaviorData($uid,BusinessProductService::VIEW_RECORD_TYPE);


        // 导航
        $versionNum = Yii::$app->request->headers->get('version-num');
        $navList = ToolsHelper::getNavListByPageType(3, ['version_num' => $versionNum]);

        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);

        // 更新用户活跃时间
        $userService = new UserService();
        $userService->updateStructureDataToUserInfoRedis($uid, ['active_at' => $now]);

        return ToolsHelper::funcReturn(
            "我的页面",
            true,
            [
                'userInfo' => $userInfo,
                'dataArr' => $dataArr,
                'navList' => $navList,
                'shopData' => $shopData,
            ]
        );
    }

    /**
     * 我发布的商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/26 20:51
     */
    public function actionProductList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $productService = new ProductService();
        $productList = $productService->getProductList($uid, $page, $pageSize);

        // 是否关注公众号
        $versionNum = Yii::$app->request->headers->get('version-num');
        if($versionNum>=40000){
            $unionService = new UnionService();
            $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);
        }else{
            $subscribe = 1;
        }

        return ToolsHelper::funcReturn(
            "商品列表",
            true,
            [
                'userInfo' => $userInfo,
                'productList' => $productList,
                'page' => $page,
                'pageSize' => $pageSize,
                'subscribe' => $subscribe
            ]
        );
    }

    /**
     * 用户卖出、浏览、点赞、评论、想要商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/26 21:21
     */
    public function actionActiveProductList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $page = $page >= 500 ? 500 : $page;
        $type = Yii::$app->request->get('type', 'view');
        $pageSize = self::PAGESIZE;

        $productService = new ProductService();
        $productList = $productService->getUserActiveProductList($uid, $type, $page, $pageSize);
        return ToolsHelper::funcReturn(
            "商品列表",
            true,
            [
                'productList' => $productList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 用户访问
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/11 10:54
     */
    public function actionVisit()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $uid = Yii::$app->request->get('uid', 0); //访问的用户uid
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;
        if (empty($uid)) {
            return ToolsHelper::funcReturn("参数错误，请重新进入");
        }

        $productService = new ProductService();
        $authorData = $productService->getUserStatisticData($uid);
        $productList = $productService->getSalingProductList($authorData, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "在售商品",
            true,
            [
                'visitUserInfo' => $userInfo,
                'productList' => $productList,
                'authorData' => $authorData,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }
}