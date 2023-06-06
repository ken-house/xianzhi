<?php
/**
 * 商品分类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/9/10 09:38
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\ProductService;
use Yii;

class CategoryController extends BaseController
{
    /**
     * 分类
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/10 11:03
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $categoryId = Yii::$app->request->get('category_id', 100000);
        $keyword = Yii::$app->request->get('keyword', '');

        $productService = new ProductService();
        // 顶级分类
        $topCategoryList = $productService->getCategoryByPid();

        // 热门推荐
        $topCategoryHotList = $productService->getCategoryByPid(0, 1);

        $topCategorySearchList = [];
        // 二级分类及三级分类
        if (!empty($keyword)) {
            $categoryId = 200000;
            $topCategorySearchList[] = [
                'id' => $categoryId,
                'category_name' => '搜索分类',
                'icon' => '',
            ];
            $categoryList = $productService->getSearchCategoryList($keyword);
        } else {
            $hot = $categoryId == 100000 ? 1 : 0;
            $categoryList = $productService->getCategoryByPid($categoryId, $hot, 1);
        }


        return ToolsHelper::funcReturn(
            '分类',
            true,
            [
                'userInfo' => $userInfo,
                'topCategoryList' => array_merge($topCategorySearchList, $topCategoryHotList, $topCategoryList),
                'categoryList' => $categoryList,
                'categoryId' => $categoryId,
            ]
        );
    }

    /**
     * 分类商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/10 11:03
     */
    public function actionProductList()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $params['dist_type'] = Yii::$app->request->get('dist_type', 0);
        $params['lat'] = Yii::$app->request->get('lat', 0);
        $params['lng'] = Yii::$app->request->get('lng', 0);
        $categoryId = Yii::$app->request->get('category_id', 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = 20;

        // 查询宝贝列表
        $productService = new ProductService();
        $productList = $productService->getCategoryProductList([$categoryId], $params, $page, $pageSize);

        return ToolsHelper::funcReturn(
            '商品分类',
            true,
            [
                'userInfo' => $userInfo,
                'productList' => array_values($productList),
                'page' => $page,
                'pageSize' => $pageSize,
                'categoryId' => $categoryId,
                'distTypeList' => ToolsHelper::getDistTypeList(1)
            ]
        );
    }
}