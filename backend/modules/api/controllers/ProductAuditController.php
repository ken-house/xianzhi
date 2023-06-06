<?php
/**
 * 商品审核
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:53
 */

namespace backend\modules\api\controllers;

use backend\services\ProductService;
use common\helpers\ToolsHelper;
use common\services\MessageService;
use common\services\RewardPointService;
use console\services\jobs\MessageJob;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class ProductAuditController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        if (YII_ENV_DEV) {
            return parent::behaviors();
        } else {
            return ArrayHelper::merge(
                [
                    'access' => [
                        'class' => AccessControl::class,
                        'rules' => [
                            [
                                'allow' => true,
                                'roles' => ['@'],
                            ]
                        ],
                    ],
                ],
                parent::behaviors()
            );
        }
    }

    /**
     * 商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:03
     */
    public function actionIndex()
    {
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['product_id'] = Yii::$app->request->get('id', 0);
        $data['product_name'] = Yii::$app->request->get('name', "");
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $data['category_id'] = Yii::$app->request->get('category_id', 0);
        $startDate = Yii::$app->request->get('start_date', date('Y-m-d'));
        $data['start_date'] = !empty($startDate) ? strtotime($startDate) : 0;
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));
        $data['end_date'] = !empty($endDate) ? strtotime($endDate) + 86399 : 0;
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $productService = new ProductService();
        $result = $productService->getProductList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['productStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['cheatList'] = ToolsHelper::convertSelectOptionArr([0 => '未作弊', 1 => '作弊']);
        $result['data']['reasonList'] = ToolsHelper::convertSelectOptionArr(Yii::$app->params['reasonList']);
        $result['data']['activityList'] = [];
        $result['data']['pageTypeList'] = ToolsHelper::convertSelectOptionArr(Yii::$app->params['stickPageTypeArr']);
        $result['data']['headerData'] = $this->getHeaderData();

        return $result;
    }

    /**
     * 商品分类
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/11 17:36
     */
    public function actionCategoryList()
    {
        $keyword = Yii::$app->request->get("keyword", "");
        $productService = new ProductService();
        $categoryList = $productService->getCategoryListByKeyword($keyword);
        return ToolsHelper::funcReturn("分类列表", true, ['categoryList' => ToolsHelper::convertSelectOptionArr($categoryList)]);
    }

    /**
     * 表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/10 16:45
     */
    private function getHeaderData()
    {
        return [
            'id' => 'ID',
            'nickname' => '昵称（UID）',
            'name' => '名称',
            'product_title' => '标题',
            'info' => '详情',
            'pics' => '图片',
            'category_name' => '分类',
            'price' => '价格',
            'location' => '所在位置',
            'wx' => '微信号',
            'phone' => '手机号',
            'subscribe' => '关注公众号',
            'updated_at' => '更新时间',
            'status_name' => '状态',
        ];
    }

    /**
     * 审核通过
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:02
     */
    public function actionPass()
    {
        $id = Yii::$app->request->post('id');
        $isCheat = Yii::$app->request->post('is_cheat', 0); // 是否为删除后重复发布，以防止骗积分
        $categoryId = Yii::$app->request->post('category_id', 0); // 商品分类
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productService = new ProductService();
        return $productService->pass($id, $categoryId, $isCheat);
    }

    /**
     * 审核不通过
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:02
     */
    public function actionRefuse()
    {
        $id = Yii::$app->request->post('id');
        $reasonId = Yii::$app->request->post('reason_id', 0);

        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $reasonList = Yii::$app->params['reasonList'];
        $auditReason = isset($reasonList[$reasonId]) ? $reasonList[$reasonId] : '';


        $productService = new ProductService();
        return $productService->refuse($id, $auditReason);
    }

    /**
     * 强制下架并删除
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/9 19:58
     */
    public function actionDown()
    {
        $id = Yii::$app->request->post('id');
        $auditReason = Yii::$app->request->post('audit_reason', '');

        if (empty($id) || empty($auditReason)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productService = new ProductService();
        return $productService->down($id, $auditReason);
    }

    /**
     * 调整分类
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/21 16:48
     */
    public function actionCategory()
    {
        $id = Yii::$app->request->post('id');
        $categoryId = Yii::$app->request->post('category_id');
        if (empty($id) || empty($categoryId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productService = new ProductService();
        return $productService->category($id, $categoryId);
    }

    /**
     * 加入或移出活动页
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/21 11:03
     */
    public function actionActivity()
    {
        $id = Yii::$app->request->post('id');
        $activityId = Yii::$app->request->post('activity_id', 0);
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productService = new ProductService();
        return $productService->activity($id, $activityId);
    }

    /**
     * 置顶或取消置顶
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/21 11:03
     */
    public function actionStick()
    {
        $params['id'] = Yii::$app->request->post('id', 0);
        $params['status'] = Yii::$app->request->post('status', 0);
        $params['type'] = Yii::$app->request->post('type', 1);
        $params['activity_id'] = Yii::$app->request->post('activity_id', 0);
        $params['start_date'] = Yii::$app->request->post('start_date', '');
        $params['end_date'] = Yii::$app->request->post('end_date', '');

        if (empty($params['id'])) {
            return ToolsHelper::funcReturn("参数错误");
        }

        if (!empty($params['status']) && empty($params['start_date']) && empty($params['end_date'])) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $productService = new ProductService();
        return $productService->stick($params);
    }
}