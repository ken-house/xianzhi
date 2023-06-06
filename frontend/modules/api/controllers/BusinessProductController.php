<?php
/**
 * 电商商品
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/16 21:44
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BusinessProductService;
use common\services\JdService;
use common\services\PddService;
use common\services\UserService;
use Yii;

class BusinessProductController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 电商商品点击进入商品详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/22 09:42
     */
    public function actionClick()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $businessProductId = Yii::$app->request->post("id");
        $sourceId = Yii::$app->request->post('source_id', 1);
        $productName = Yii::$app->request->post('product_name', '');
        $keyword = Yii::$app->request->post("keyword", "");
        $type = Yii::$app->request->post('type', 1); // 1 浏览 2 加入购物车
        if (empty($keyword)) {
            return ToolsHelper::funcReturn("无效记录");
        }

        // 记录用户的浏览记录
        $businessProductService = new BusinessProductService();
        $businessProductService->record($uid, $businessProductId, $sourceId, $productName, $type);

        // 记录用户搜索的关键词
        $userService = new UserService();
        $userService->saveUserFavourite($uid, $keyword);

        return ToolsHelper::funcReturn("点击成功", true);
    }

    /**
     * 用户操作电商商品浏览、加入购物车
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/16 21:49
     */
    public function actionUserBehavior()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $page = Yii::$app->request->get('page', 1);
        $type = Yii::$app->request->get('type', 1); // 1 用户浏览  2 加入购物车
        $keyword = Yii::$app->request->get('keyword', '');
        $pageSize = self::PAGESIZE;

        $businessProductService = new BusinessProductService();
        $productList = $businessProductService->getRecordListByType($uid, $type, $keyword, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "用户操作电商商品",
            true,
            [
                'userInfo' => $userInfo,
                'productList' => $productList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 移出购物车
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/17 20:48
     */
    public function actionRemoveShopcar()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $businessProductId = Yii::$app->request->post("id");
        $sourceId = Yii::$app->request->post('source_id');

        $businessProductService = new BusinessProductService();
        $res = $businessProductService->removeShopcar($uid, $businessProductId, $sourceId);
        if ($res) {
            return ToolsHelper::funcReturn("移出购物车成功", true);
        }
        return ToolsHelper::funcReturn("移出购物车失败");
    }


    /**
     * 精选频道数据
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/29 11:09
     */
    public function actionChosen()
    {
        $chosenData = ToolsHelper::getBusinessProductChannelType();

        $businessProductService = new BusinessProductService();
        $productList = $businessProductService->getBusinessProductByRecommend($chosenData['source_id'], 1, 50, $chosenData['channel_type']);
        shuffle($productList);

        return ToolsHelper::funcReturn(
            "精选频道",
            true,
            [
                'productList' => array_slice($productList, 0, 4),
                'chosenData' => $chosenData,
            ]
        );
    }
}