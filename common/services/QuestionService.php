<?php
/**
 * 答题服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/17 15:00
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Question;
use Yii;

class QuestionService
{
    /** @var \redisCluster $redisBaseCluster */
    private $redisBaseCluster;
    private $redisKey;
    private $uid;

    public function __construct($uid)
    {
        $this->redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $this->redisKey = RedisHelper::RK('userQuestion');
        $this->uid = $uid;
    }

    /**
     * 获取题库最大的id值
     *
     * @return false|string|null
     *
     * @author     xudt
     * @date-time  2021/5/17 15:01
     */
    private function getMaxQuestionId()
    {
        return Question::find()->select(['id'])->orderBy('id desc')->limit(1)->scalar();
    }

    /**
     * 获取本次选择出现的题
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/5/17 15:30
     */
    public function getSelectedQuestionInfo()
    {
        $maxId = $this->getMaxQuestionId();
        $questionId = $this->redisBaseCluster->zScore($this->redisKey, $this->uid);
        $selectQuestionInfo = [];
        // 是否完成每日任务
        $rewardPointService = new RewardPointService(RewardPointService::QUESTION_AWARD_TYPE, $this->uid, time());
        $isFinishTask = $rewardPointService->isFinishOnceReward();
        if (!$isFinishTask) { // 未完成时
            if ($questionId < $maxId) {
                for ($i = 1; $i <= $maxId; $i++) {
                    $questionId = $questionId + 1;
                    $selectQuestionInfo = Question::find()->where(['id' => $questionId, 'status' => 1])->asArray()->one();
                    break;
                }
            }
        } else { // 已完成
            $selectQuestionInfo = Question::find()->where(['id' => $questionId])->asArray()->one();
        }
        if (!empty($selectQuestionInfo)) {
            $selectQuestionInfo['answer_list'] = json_decode($selectQuestionInfo['answer_list'], true);
        }
        return [
            'questionInfo' => $selectQuestionInfo,
            'isFinishTask' => $isFinishTask
        ];
    }


    /**
     * 用户答题
     *
     * @param $id
     * @param $answer
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/17 16:16
     */
    public function answerQuestion($id, $answer)
    {
        $now = time();
        $answerRight = 0;
        $questionInfo = Question::find()->where(['id' => $id])->asArray()->one();
        if ($questionInfo['answer'] == $answer) { // 答对
            $answerRight = 1;

            // 记录用户答对题的id
            $this->redisBaseCluster->zAdd($this->redisKey, $id, $this->uid);

            // 发放奖励
            $rewardPointService = new RewardPointService(RewardPointService::QUESTION_AWARD_TYPE, $this->uid, $now);
            $rewardPointService->onceTaskAwardPoint();
        }

        $data = [
            'answerRight' => $answerRight,
            'rewardPoint' => Yii::$app->params['awardType'][RewardPointService::QUESTION_AWARD_TYPE]['point'],
        ];

        return ToolsHelper::funcReturn("答题结果", true, $data);
    }
}