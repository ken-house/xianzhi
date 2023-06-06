<?php
/**
 * 兼职服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/4 18:29
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\ParttimeApply;
use common\models\ParttimeJob;
use common\models\User;
use Yii;
use yii\db\Exception;

class ParttimeJobService
{
    const STATUS_AUDIT = 0; // 待审核
    const STAUTS_PASS = 1; // 通过
    const STATUS_REFUSE = 2; // 不通过
    const STATUS_DEL = 3; // 删除
    const APPLY_OK = 1; // 已报名申请

    /**
     * 获取兼职信息
     *
     * @param $jobId
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/10 14:08
     */
    public function getJobInfo($jobId)
    {
        $jobInfo = ParttimeJob::find()->select(['id', 'uid', 'title', 'pics'])->where(['id' => $jobId])->asArray()->one();
        if (empty($jobInfo)) {
            return [];
        }
        $picArr = json_decode($jobInfo['pics'], true);
        $jobInfo['cover'] = ToolsHelper::getLocalImg($picArr[0], '', 240);
        unset($jobInfo['pics']);
        return $jobInfo;
    }

    /**
     * 获取附近的兼职
     *
     * @param       $uid
     * @param array $params
     * @param int   $page
     * @param int   $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/7 14:57
     */
    public function getJobList($uid, $params = [], $page = 1, $pageSize = 20)
    {
        $jobArr = [];
        if (!empty($params['lat'])) {
            $distList = $this->getJobListFromGeoRedis($params['lat'], $params['lng'], 50);
            if (!empty($distList)) {
                foreach ($distList as $key => $value) {
                    $jobArr[$value[0]] = $value[1] * 1000;
                }
            }
            if (empty($jobArr)) {
                return [];
            }
        }


        $start = ($page - 1) * $pageSize;
        $query = ParttimeJob::find()->where(['status' => self::STAUTS_PASS])->andWhere(['LIKE', 'title', $params['keyword']])->orderBy(['updated_at' => 'DESC'])->offset($start)->limit($pageSize);
        if (!empty($jobArr)) {
            $query->andWhere(['id' => array_keys($jobArr)]);
        }
        $dataList = $query->asArray()->all();
        if (!empty($dataList)) {
            $uidArr = [];
            foreach ($dataList as $k => $v) {
                $uidArr[] = $v['uid'];
            }
            $userInfoArr = User::find()->where(['id' => $uidArr])->indexBy('id')->asArray()->all();
            foreach ($dataList as $key => &$value) {
                $picArr = json_decode($value['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $value['pics'] = $picArr;
                $dist = isset($jobArr[$value['id']]) ? $jobArr[$value['id']] : 0;
                switch (true) {
                    case $dist > 1000:
                        $value['dist'] = round($dist / 1000, 1) . "km";
                        break;
                    case $dist <= 1000 && $dist > 200:
                        $value['dist'] = intval($dist) . '米';
                        break;
                    case $dist <= 200 && $dist > 0:
                        $value['dist'] = '200米以内';
                        break;
                    default:
                        $value['dist'] = '';
                }
                $value['publish_time'] = $value['status'] == self::STAUTS_PASS ? ToolsHelper::getTimeStrDiffNow($value['updated_at']) : '';

                // 作者信息
                $value['nickname'] = isset($userInfoArr[$value['uid']]['nickname']) ? $userInfoArr[$value['uid']]['nickname'] : '';
                $value['avatar'] = isset($userInfoArr[$value['uid']]['avatar']) ? $userInfoArr[$value['uid']]['avatar'] : '';
                $value['gender'] = isset($userInfoArr[$value['uid']]['gender']) ? $userInfoArr[$value['uid']]['gender'] : 0;
                $value['renzheng'] = $value['uid'] == 100001 ? 1 : 0;
                $value['view_num'] = $this->getJobData($value['id'], 'view_num');
            }
        }
        return $dataList;
    }

    /**
     * 从geoRedis中读取附近的兼职列表
     *
     * @param $lat
     * @param $lng
     * @param $distType
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:44
     */
    private function getJobListFromGeoRedis($lat, $lng, $distType)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoJob');
        return $redisClient->georadius($redisKey, floatval($lng), floatval($lat), $distType, 'km', 'WITHDIST');
    }

    /**
     * 兼职列表
     *
     * @param int $uid
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/4 19:04
     */
    public function getMyJobList($uid, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        $dataList = ParttimeJob::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL])->orderBy(['status' => 'ASC', 'updated_at' => 'DESC'])->offset($start)->limit($pageSize)->asArray()->all();
        if (!empty($dataList)) {
            foreach ($dataList as $key => &$value) {
                $picArr = json_decode($value['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $value['pics'] = $picArr;
                $value['cover'] = isset($picArr[0]) ? ToolsHelper::getLocalImg($picArr[0]) : '';
                $value['view_num'] = $this->getJobData($value['id'], 'view_num');
            }
        }
        return $dataList;
    }

    /**
     * 我发布的兼职数量
     *
     * @param $uid
     *
     * @return bool|int|string|null
     *
     * @author     xudt
     * @date-time  2021/11/8 16:18
     */
    public function getMyJobCount($uid)
    {
        return ParttimeJob::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL])->count();
    }

    /**
     * 兼职详情
     *
     * @param $uid
     * @param $jobId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 18:46
     */
    public function getJobInfoFromDb($uid, $jobId = 0)
    {
        $jobInfo = ParttimeJob::find()->where(['id' => $jobId])->asArray()->one();
        if (empty($jobInfo)) {
            return ToolsHelper::funcReturn("该兼职不存在");
        }

        if ($jobInfo['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $jobInfo['pics'] = !empty($jobInfo['pics']) ? json_decode($jobInfo['pics'], true) : [];
        if (!empty($jobInfo['pics'])) {
            foreach ($jobInfo['pics'] as $k => $picUrl) {
                $jobInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl);
            }
        }

        $jobInfo['info'] = preg_replace("/<p\/>/", "\n", $jobInfo['info']);
        $jobInfo['people_num'] .= "人";
        $jobInfo['settle_type'] = isset(Yii::$app->params['jobSettleTypeArr'][$jobInfo['settle_type']]) ? Yii::$app->params['jobSettleTypeArr'][$jobInfo['settle_type']] : '';

        return ToolsHelper::funcReturn("兼职详情", true, ['jobInfo' => $jobInfo]);
    }


    /**
     * 保存兼职信息
     *
     * @param $userInfo
     * @param $jobInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function saveJobInfoToDb($userInfo, $jobInfo)
    {
        $now = time();
        $jobInfo['info'] = preg_replace("/[\n\r]/", "<p/>", $jobInfo['info']);
        $jobInfo['pics'] = str_replace(Yii::$app->params['assetDomain'], "", $jobInfo['pics']);

        // 招聘人数
        $jobInfo['people_num'] = intval($jobInfo['people_num']);
        // 结算方式
        $settleTypeArr = Yii::$app->params['jobSettleTypeArr'];
        foreach ($settleTypeArr as $typeId => $typeName) {
            if ($typeName == $jobInfo['settle_type']) {
                $jobInfo['settle_type'] = $typeId;
                break;
            }
        }
        if ($jobInfo['settle_type'] == 0) {
            $jobInfo['salary'] = 0;
        }

        if ($jobInfo['id'] == 0) {
            $parttimeJobModel = new ParttimeJob();
            $jobInfo['uid'] = $userInfo['uid'];
            $jobInfo['created_at'] = $now;
        } else {
            $parttimeJobModel = ParttimeJob::find()->where(['id' => $jobInfo['id']])->one();
            if ($parttimeJobModel->uid != $userInfo['uid']) {
                return ToolsHelper::funcReturn("非法操作");
            }
        }
        $jobInfo['status'] = self::STATUS_AUDIT;
        $jobInfo['updated_at'] = $now;
        $parttimeJobModel->attributes = $jobInfo;
        if ($parttimeJobModel->save()) {
            return ToolsHelper::funcReturn("发布成功，审核期间不展示该兼职信息，两小时内完成审核", true);
        }
        return ToolsHelper::funcReturn("发布失败");
    }


    /**
     * 兼职详情页面数据
     *
     * @param     $jobId
     * @param int $uid
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/11/4 19:01
     */
    public function getJobPageData($jobId, $uid = 0)
    {
        $jobInfo = ParttimeJob::find()->where(['id' => $jobId])->asArray()->one();
        if (empty($jobInfo)) {
            return [];
        }

        $jobInfo['pics'] = !empty($jobInfo['pics']) ? json_decode($jobInfo['pics'], true) : [];
        if (!empty($jobInfo['pics'])) {
            foreach ($jobInfo['pics'] as $k => $picUrl) {
                $jobInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl, '', 540);
            }
        }

        $jobInfo['info'] = str_replace("<p/>", "<br>", $jobInfo['info']);
        $jobInfo['publish_time'] = $jobInfo['status'] == self::STAUTS_PASS ? ToolsHelper::getTimeStrDiffNow($jobInfo['updated_at']) : '';
        $jobInfo['view_num'] = $this->getJobData($jobId, 'view_num');

        // 获取用户活跃时间
        $userService = new UserService();

        $authorInfo = $userService->getUserAllDataFromRedisMysql($jobInfo['uid']);
        $authorInfo['active_time'] = intval($authorInfo['active_at']) > 0 ? ToolsHelper::getTimeStrDiffNow($authorInfo['active_at']) : '';

        //用户统计数据
        $productService = new ProductService();
        $authorData = $productService->getUserStatisticData($jobInfo['uid']);

        return [
            'jobInfo' => $jobInfo,
            'authorInfo' => array_merge($authorInfo, $authorData),
        ];
    }

    /**
     * 删除兼职工作
     *
     * @param $userInfo
     * @param $jobId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 19:06
     */
    public function deleteJob($userInfo, $jobId)
    {
        $now = time();
        if (empty($jobId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $parttimeJobModel = ParttimeJob::find()->where(['id' => $jobId])->one();
        if ($parttimeJobModel->uid != $userInfo['uid']) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $status = self::STATUS_DEL;
        $parttimeJobModel->status = $status;
        $parttimeJobModel->audit_reason = '';
        $parttimeJobModel->updated_at = $now;
        if ($parttimeJobModel->save()) {
            return ToolsHelper::funcReturn("操作成功", true, ['status' => $status]);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 报名或取消报名
     *
     * @param $userInfo
     * @param $jobId
     * @param $status
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 19:33
     */
    public function applyJob($userInfo, $jobId, $status)
    {
        $now = time();
        if (empty($jobId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $parttimeJobModel = ParttimeJob::find()->where(['id' => $jobId])->one();
        if ($parttimeJobModel->uid == $userInfo['uid']) {
            return ToolsHelper::funcReturn("发布者不可报名");
        }

        if ($parttimeJobModel->status != self::STAUTS_PASS) {
            return ToolsHelper::funcReturn("该兼职信息已删除");
        }

        $parttimeApplyModel = ParttimeApply::find()->where(['uid' => $userInfo['uid'], 'job_id' => $jobId])->one();
        if (!empty($parttimeApplyModel) && $parttimeApplyModel->status == $status) {
            return ToolsHelper::funcReturn("重复操作");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (empty($parttimeApplyModel)) {
                $parttimeApplyModel = new ParttimeApply();
                $parttimeApplyModel->uid = $userInfo['uid'];
                $parttimeApplyModel->job_id = $jobId;
            }
            $parttimeApplyModel->status = $status;
            $parttimeApplyModel->created_at = $now;
            $parttimeApplyModel->updated_at = $now;
            $res1 = $parttimeApplyModel->save();

            // 更改兼职工作的报名人数
            if ($status) {
                $parttimeJobModel->apply_num += 1;
                $message = "报名成功";
            } else {
                $parttimeJobModel->apply_num -= 1;
                $message = "取消报名成功";
            }
            $parttimeJobModel->updated_at = $now;
            $res2 = $parttimeJobModel->save();
            if ($res1 && $res2) {
                $transaction->commit();
                return ToolsHelper::funcReturn($message, true);
            }
            $transaction->rollBack();
            return ToolsHelper::funcReturn("操作失败");
        } catch (Exception $e) {
            $transaction->rollBack();
            return ToolsHelper::funcReturn("操作失败");
        }
    }

    /**
     * 我报名的
     *
     * @param     $uid
     * @param int $page
     * @param int $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/11/4 21:21
     */
    public function getApplyList($uid, $page = 1, $pageSize = 20)
    {
        $start = ($page - 1) * $pageSize;
        $jobIdArr = ParttimeApply::find()->select(['job_id'])->where(['uid' => $uid, 'status' => 1])->offset($start)->limit($pageSize)->column();
        $jobList = [];
        if (!empty($jobIdArr)) {
            $jobList = ParttimeJob::find()->where(['id' => $jobIdArr])->asArray()->all();
        }
        return $jobList;
    }

    /**
     * 添加兼职的位置信息到geoRedis中
     *
     * @param $jobId
     * @param $lat
     * @param $lng
     *
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:37
     */
    public function addJobGeoData($jobId, $lat, $lng)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoJob');

        $redisClient->geoadd($redisKey, $lng, $lat, $jobId);
    }

    /**
     * 获取兼职数据
     *
     * @param        $jobId
     * @param string $hashKey
     *
     * @return array|int
     *
     * @author     xudt
     * @date-time  2021/3/20 15:04
     */
    public function getJobData($jobId, $hashKey = '')
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("jobData", $jobId);
        if (empty($hashKey)) {
            return $redisBaseCluster->hGetAll($redisKey);
        } else {
            return intval($redisBaseCluster->hGet($redisKey, $hashKey));
        }
    }

    /**
     * 增加浏览数据
     *
     * @param $jobId
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/9 10:46
     */
    public function incrViewJobData($jobId)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('jobData', $jobId);
        return $redisBaseCluster->hIncrBy($redisKey, 'view_num', 1);
    }

}