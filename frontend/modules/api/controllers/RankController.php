<?php
/**
 * 排行榜数据
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/12 06:27
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\UserService;
use Yii;

class RankController extends BaseController
{
    /**
     * 用户积分排行榜-前100名
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/12 06:45
     */
    public function actionPoint()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $type = Yii::$app->request->get('type', 0); // 0 今日 1 昨日

        $userService = new UserService();
        $rankList = $userService->getUserPointRank($type);

        $myRankNum = isset($rankList[$uid]) ? $rankList[$uid]['rank'] : 0;

        return ToolsHelper::funcReturn(
            "用户积分排行榜",
            true,
            [
                'userInfo' => $userInfo,
                'rankList' => array_values($rankList),
                'myRankNum' => $myRankNum,
                'type' => $type,
            ]
        );
    }

}