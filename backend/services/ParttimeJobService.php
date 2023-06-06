<?php
/**
 * 兼职审核
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/4 22:13
 */

namespace backend\services;

use common\helpers\ToolsHelper;
use common\models\ParttimeJob;
use common\models\User;
use common\services\MessageService;
use common\services\ParttimeJobService as CommonParttimeJobService;
use console\services\jobs\MessageJob;
use Yii;
use yii\helpers\ArrayHelper;

class ParttimeJobService
{
    /**
     * 兼职信息列表
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 22:30
     */
    public function getJobList($data = [])
    {
        $parttimeJobModel = ParttimeJob::find()->andFilterWhere(['LIKE', 'title', $data['title']]);
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $parttimeJobModel->andWhere(['>=', 'updated_at', $data['start_date']])->andWhere(['<=', 'updated_at', $data['end_date']]);
        }
        if (!empty($data['job_id'])) {
            $parttimeJobModel->andWhere(['id' => $data['job_id']]);
        }
        if (!empty($data['uid'])) {
            $parttimeJobModel->andWhere(['uid' => $data['uid']]);
        }
        if ($data['status'] != -1) {
            $parttimeJobModel->andWhere(['status' => $data['status']]);
        }
        $parttimeJobCountModel = clone $parttimeJobModel;
        $count = $parttimeJobCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $parttimeJobModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();

        $reasonList = Yii::$app->params['reasonList'];

        // 获取用户信息
        $uidArr = ArrayHelper::getColumn($list, 'uid');
        $userArr = [];
        if (!empty($uidArr)) {
            $userArr = User::find()->select(['id','nickname', 'phone'])->where(['id' => $uidArr])->asArray()->indexBy('id')->all();
        }

        if (!empty($list)) {
            foreach ($list as $key => &$value) {
                $picList = json_decode($value['pics'], true);
                $imageUrl = [];
                foreach ($picList as $url) {
                    $imageUrl[] = ToolsHelper::getLocalImg($url, '', 540);
                }

                $reasonId = array_search($value['audit_reason'], $reasonList);
                $value['reason_id'] = intval($reasonId);
                $value['job_title'] = $value['title'];
                $value['pics'] = $imageUrl;
                $value['nickname'] = isset($userArr[$value['uid']]['nickname']) ? $userArr[$value['uid']]['nickname'] : '';
                $value['phone'] = isset($userArr[$value['uid']]['phone']) ? $userArr[$value['uid']]['phone'] : '';
                $value['status_name'] = Yii::$app->params['jobStatus'][$value['status']];
                $value['updated_at'] = !empty($value['updated_at']) ? date("Y-m-d H:i:s", $value['updated_at']) : '';
                $value['settle_name'] = Yii::$app->params['jobSettleTypeArr'][$value['settle_type']];
            }
        }

        return ToolsHelper::funcReturn(
            "兼职信息列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }

    /**
     * 审核通过
     *
     * @param $id
     * @param $categoryId
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/11/4 22:51
     */
    public function pass($id, $categoryId)
    {
        $now = time();
        $parttimeJobModel = ParttimeJob::find()->where(['id' => $id])->one();
        if (empty($parttimeJobModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($parttimeJobModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 如果不填分类id，则使用原分类id
        if (empty($categoryId)) {
            $categoryId = $parttimeJobModel->category_id;
        }

        // 检查用户的微信是否绑定，若未绑定，直接审核不通过
        $userInfo = User::find()->where(['id' => $parttimeJobModel->uid])->asArray()->one();
        if (empty($userInfo['phone'])) {
            return $this->refuse($id, "手机号未认证");
        }

        // 修改数据库
        $parttimeJobModel->category_id = $categoryId;
        $parttimeJobModel->status = CommonParttimeJobService::STAUTS_PASS;
        $parttimeJobModel->audit_reason = '';
        $parttimeJobModel->audit_at = $now;
        $parttimeJobModel->updated_at = $now;
        if ($parttimeJobModel->save()) {
            //写入geoRedis
            $commonParttimeJobService = new CommonParttimeJobService();
            $commonParttimeJobService->addJobGeoData($id, $parttimeJobModel->lat, $parttimeJobModel->lng);

            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => MessageService::SYSTEM_USER
                                ],
                                'jobId' => $parttimeJobModel->id,
                                'messageType' => MessageService::SYSTEM_JOB_AUDIT_PASS_MESSAGE,
                            ]
                        ]
                    ]
                )
            );

            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败", true);
    }

    /**
     * 审核拒绝
     *
     * @param $id
     * @param $auditReason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 22:53
     */
    public function refuse($id, $auditReason)
    {
        $now = time();
        $parttimeJobModel = ParttimeJob::find()->where(['id' => $id])->one();
        if (empty($parttimeJobModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($parttimeJobModel->status != 0) {
            return ToolsHelper::funcReturn("非待审核");
        }

        // 修改数据库
        $parttimeJobModel->status = CommonParttimeJobService::STATUS_REFUSE;
        $parttimeJobModel->audit_reason = $auditReason;
        $parttimeJobModel->audit_at = $now;
        $parttimeJobModel->updated_at = $now;
        if ($parttimeJobModel->save()) {
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => MessageService::SYSTEM_USER
                                ],
                                'jobId' => $parttimeJobModel->id,
                                'messageType' => MessageService::SYSTEM_JOB_AUDIT_REFUSE_MESSAGE,
                                'audit_reason' => $auditReason
                            ]
                        ]
                    ]
                )
            );
            return ToolsHelper::funcReturn("审核不通过成功", true);
        }
        return ToolsHelper::funcReturn("审核不通过失败", true);
    }

    /**
     * 强制下架
     *
     * @param $id
     * @param $auditReason
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 22:57
     */
    public function down($id, $auditReason)
    {
        $now = time();
        $parttimeJobModel = ParttimeJob::find()->where(['id' => $id])->one();
        if (empty($parttimeJobModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }
        if ($parttimeJobModel->status != 1) {
            return ToolsHelper::funcReturn("非已上线商品");
        }

        // 修改数据库
        $parttimeJobModel->status = CommonParttimeJobService::STATUS_DEL;
        $parttimeJobModel->audit_reason = $auditReason;
        $parttimeJobModel->audit_at = $now;
        $parttimeJobModel->updated_at = $now;
        if ($parttimeJobModel->save()) {
            // 发送系统消息
            Yii::$app->messageQueue->push(
                new MessageJob(
                    [
                        'data' => [
                            [
                                'userInfo' => [
                                    'uid' => MessageService::SYSTEM_USER
                                ],
                                'jobId' => $parttimeJobModel->id,
                                'messageType' => MessageService::SYSTEM_JOB_AUDIT_DOWN_MESSAGE,
                            ]
                        ]
                    ]
                )
            );
            return ToolsHelper::funcReturn("操作成功", true);
        }
        return ToolsHelper::funcReturn("操作失败");
    }
}