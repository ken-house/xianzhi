<?php
/**
 * 兼职
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/4 18:24
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BannerService;
use common\services\ParttimeJobService;
use common\services\UnionService;
use Yii;

class ParttimeJobController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 表单默认值
     *
     * @var array
     */
    private $jobInfo = [
        'id' => 0,
        'title' => '',
        'people_num' => '',
        'info' => '',
        'pics' => [],
        'settle_type' => '',
        'location' => '',
        'lat' => 0,
        'lng' => 0,
        'salary' => '',
    ];

    /**
     * 兼职工作列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 18:31
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);
        $params['keyword'] = Yii::$app->request->get('keyword', '');
        $params['lat'] = Yii::$app->request->get('lat', 0);
        $params['lng'] = Yii::$app->request->get('lng', 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $parttimeJobService = new ParttimeJobService();
        $jobList = $parttimeJobService->getJobList($uid, $params, $page, $pageSize);

        // banner列表
        $bannerService = new BannerService();
        $bannerList = $bannerService->getBannerList($params['lat'], $params['lng'], BannerService::BANNER_JOB);

        $distTypeList = ToolsHelper::getDistTypeList(1);
        $versionNum = Yii::$app->request->headers->get('version-num');

        // 导航
        $navList = Yii::$app->params['parttimeJobNavList'];

        return ToolsHelper::funcReturn(
            "兼职首页",
            true,
            [
                'userInfo' => $userInfo,
                'jobList' => $jobList,
                'distTypeList' => $distTypeList,
                'navList' => $navList,
                'bannerList' => $bannerList,
                'page' => $page,
                'pageSize' => $pageSize,
                'showButton' => ToolsHelper::showButton($versionNum),
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
        $jobId = Yii::$app->request->get('id', 0);

        $jobInfo = $this->jobInfo;
        if ($jobId != 0) {
            $parttimeJobService = new ParttimeJobService();
            $result = $parttimeJobService->getJobInfoFromDb($userInfo['uid'], $jobId);
            if (!$result['result']) {
                return $result;
            }
            $jobInfo = $result['data']['jobInfo'];
        }

        // 是否关注公众号
        $unionService = new UnionService();
        $subscribe = $unionService->isSubscribe($userInfo['wx_openid']);

        return ToolsHelper::funcReturn(
            "兼职发布",
            true,
            [
                'userInfo' => $userInfo,
                'jobInfo' => $jobInfo,
                'subscribe' => $subscribe,
                'settleTypeArr' => Yii::$app->params['jobSettleTypeArr'],
            ]
        );
    }

    /**
     * 保存兼职到数据库中
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function actionSave()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $jobInfo = Yii::$app->request->post(); // 打卡详情

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->saveJobInfoToDb($userInfo, $jobInfo);
    }


    /**
     * 兼职详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 18:53
     */
    public function actionInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        // 会员等级
        $userInfo['level'] = ToolsHelper::getUserLevel($userInfo['reward_point']);

        $jobId = Yii::$app->request->get('id', 0);

        $parttimeJobService = new ParttimeJobService();
        $jobResult = $parttimeJobService->getJobPageData($jobId, $uid);
        if (empty($jobResult)) {
            return ToolsHelper::funcReturn("请在后台关闭微信，重新进入小程序");
        }

        // 导航
        $versionNum = Yii::$app->request->headers->get('version-num');
        $navList = ToolsHelper::getNavListByPageType(2, ['version_num' => $versionNum]);

        $data = [
            'userInfo' => $userInfo,
            'jobInfo' => $jobResult['jobInfo'],
            'authorInfo' => $jobResult['authorInfo'],
            'navList' => $navList,
            'showButton' => ToolsHelper::showButton($versionNum),
        ];

        // 更新浏览数Redis
        $parttimeJobService->incrViewJobData($jobId);

        return ToolsHelper::funcReturn('商品详情', true, $data);
    }

    /**
     * 我的发布列表
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

        $parttimeJobService = new ParttimeJobService();
        $jobList = $parttimeJobService->getMyJobList($uid, $page, $pageSize);
        return ToolsHelper::funcReturn(
            "我的兼职发布列表",
            true,
            [
                'userInfo' => $userInfo,
                'jobList' => $jobList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 删除兼职
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:50
     */
    public function actionDelete()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $jobId = Yii::$app->request->post('id', 0);

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->deleteJob($userInfo, $jobId);
    }


    /**
     * 申请/取消申请报名
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 21:15
     */
    public function actionApply()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $jobId = Yii::$app->request->post('id', 0);
        $status = Yii::$app->request->post('status', 1); // 1 报名 0取消报名

        $parttimeJobService = new ParttimeJobService();
        return $parttimeJobService->applyJob($userInfo, $jobId, $status);
    }

    /**
     * 我报名的兼职
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/4 21:23
     */
    public function actionApplyList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $parttimeJobService = new ParttimeJobService();
        $jobList = $parttimeJobService->getApplyList($uid, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "我报名的兼职",
            true,
            [
                'userInfo' => $userInfo,
                'jobList' => $jobList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }
}