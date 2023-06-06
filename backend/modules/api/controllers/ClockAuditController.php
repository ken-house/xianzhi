<?php
/**
 * 打卡审核
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:53
 */

namespace backend\modules\api\controllers;

use backend\services\ClockService;
use common\helpers\ToolsHelper;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class ClockAuditController extends BaseController
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
     * 打卡列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/28 12:03
     */
    public function actionIndex()
    {
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $startDate = Yii::$app->request->get('start_date', date('Y-m-d'));
        $data['start_date'] = !empty($startDate) ? strtotime($startDate) : 0;
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));
        $data['end_date'] = !empty($endDate) ? strtotime($endDate) + 86439 : 0;
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $clockService = new ClockService();
        $result = $clockService->getClockList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['clockStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['cheatList'] = ToolsHelper::convertSelectOptionArr([0 => '未作弊', 1 => '作弊']);
        $result['data']['reasonList'] = ToolsHelper::convertSelectOptionArr(Yii::$app->params['clockReasonList']);
        $result['data']['headerData'] = $this->getHeaderData();

        return $result;
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
            'name' => '打卡地',
            'clock_title' => '标题',
            'info' => '详情',
            'pics' => '图片',
            'price' => '人均价格',
            'location' => '所在位置',
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
        if (empty($id)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $clockService = new ClockService();
        return $clockService->pass($id, $isCheat);
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

        $reasonList = Yii::$app->params['clockReasonList'];
        $auditReason = isset($reasonList[$reasonId]) ? $reasonList[$reasonId] : '';


        $clockService = new ClockService();
        return $clockService->refuse($id, $auditReason);
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

        $clockService = new ClockService();
        return $clockService->down($id);
    }
}