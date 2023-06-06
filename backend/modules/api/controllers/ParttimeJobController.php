<?php
/**
 * 兼职审核
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/4 22:10
 */

namespace backend\modules\api\controllers;

use backend\services\ParttimeJobService;
use common\helpers\ToolsHelper;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class ParttimeJobController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
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

    /**
     * 兼职信息列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:03
     */
    public function actionIndex()
    {
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['job_id'] = Yii::$app->request->get('id', 0);
        $data['title'] = Yii::$app->request->get('title', '');
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $startDate = Yii::$app->request->get('start_date', date('Y-m-d'));
        $data['start_date'] = !empty($startDate) ? strtotime($startDate) : 0;
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));
        $data['end_date'] = !empty($endDate) ? strtotime($endDate) + 86439 : 0;
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $parttimeJobService = new ParttimeJobService();
        $result = $parttimeJobService->getJobList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['jobStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['reasonList'] = ToolsHelper::convertSelectOptionArr(Yii::$app->params['jobReasonList']);
        $result['data']['headerData'] = $this->getHeaderData();

        return $result;
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
        $categoryId = Yii::$app->request->post('category_id', 0); // 商品分类
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->pass($id, $categoryId);
    }

    /**
     * 审核拒绝
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 22:53
     */
    public function actionRefuse()
    {
        $id = Yii::$app->request->post('id');
        $reasonId = Yii::$app->request->post('reason_id', 0);

        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $reasonList = Yii::$app->params['jobReasonList'];
        $auditReason = isset($reasonList[$reasonId]) ? $reasonList[$reasonId] : '';


        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->refuse($id, $auditReason);
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

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->down($id, $auditReason);
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

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->category($id, $categoryId);
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
            'phone' => '手机号',
            'title' => '标题',
            'info' => '详情',
            'pics' => '图片',
            'settle_name' => '结算方式',
            'salary' => '薪资',
            'location' => '所在位置',
            'updated_at' => '更新时间',
            'status_name' => '状态',
        ];
    }


}