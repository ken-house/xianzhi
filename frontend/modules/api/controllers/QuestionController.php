<?php
/**
 * 答题
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/17 14:22
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\models\Question;
use common\services\QuestionService;
use common\services\RewardPointService;
use Yii;

class QuestionController extends BaseController
{
    /**
     * 添加题目
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/17 14:33
     */
    public function actionAdd()
    {
        $answerList = [
            1 => '早上10点',
            2 => '早上8点',
        ];

        $data = [
            'title' => '每日积分排行榜奖励每天几点发放？',
            'answer_list' => json_encode($answerList, 310),
            'answer' => 1,
            'info' => '每日积分排行榜前三名将分别获得1w、5k、2k积分，每天早上8点准时发放奖励。',
            'status' => 1,
            'created_at' => time()
        ];

        $questionModel = new Question();
        $questionModel->attributes = $data;
        if ($questionModel->save()) {
            return ToolsHelper::funcReturn("写入成功", true);
        }
        return ToolsHelper::funcReturn("写入失败");
    }


    /**
     * 今日题目详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/17 15:36
     */
    public function actionInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $questionService = new QuestionService($uid);
        $questionRes = $questionService->getSelectedQuestionInfo();
        return ToolsHelper::funcReturn(
            "答题详情",
            true,
            [
                'userInfo' => $userInfo,
                'questionInfo' => $questionRes['questionInfo'],
                'isFinishTask' => $questionRes['isFinishTask'],
                'rewardPoint' => Yii::$app->params['awardType'][RewardPointService::QUESTION_AWARD_TYPE]['point'],
            ]
        );
    }

    /**
     * 用户答题
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/17 16:17
     */
    public function actionAnswer()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $id = Yii::$app->request->post('id', 0);
        $answer = Yii::$app->request->post('answer', 0);

        $questionService = new QuestionService($uid);
        return $questionService->answerQuestion($id, $answer);
    }
}