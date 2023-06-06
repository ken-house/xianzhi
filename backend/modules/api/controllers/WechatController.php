<?php
/**
 * 微信号联盟管理
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/7/5 17:07
 */

namespace backend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\models\Wechat;
use common\models\WechatOwner;
use Yii;

class WechatController extends BaseController
{
    /**
     * 添加黑名单
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/5 17:16
     */
    public function actionBlack()
    {
        $wx = Yii::$app->request->post("wx", "");
        if (empty($wx)) {
            return ToolsHelper::funcReturn("微信号为空");
        }
        $wechatModel = Wechat::find()->where(['wx' => $wx])->one();
        if (empty($wechatModel)) {
            $wechatModel = new Wechat();
            $wechatModel->loadDefaultValues();
        }
        if (strpos($wx, "wxid_") !== false) {
            $wechatModel->enabled = 0;
        }
        $wechatModel->wx = $wx;
        $wechatModel->type = 999;
        $wechatModel->created_at = time();
        if ($wechatModel->save()) {
            return ToolsHelper::funcReturn("添加黑名单成功", true);
        }
        return ToolsHelper::funcReturn("添加黑名单失败");
    }

    /**
     * 微信号巡逻车
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/5 19:13
     */
    public function actionRollout()
    {
        $backList = Wechat::find()->select(["wx"])->where(['type' => 999])->column();

        $ownerList = WechatOwner::find()->select(["wx"])->column();

        //  查找大本营是否存在黑名单用户
        $warnList = array_intersect($backList, $ownerList);

        // 查找加入大本营超过两人的用户列表
        $joinWarnList = WechatOwner::find()->select("wx,count(*) c")->groupBy('wx')->having("c>=2")->asArray()->all();

        return ToolsHelper::funcReturn(
            "巡逻车",
            true,
            [
                'warnList' => array_values($warnList),
                'joinWarnList' => array_values($joinWarnList),
            ]
        );
    }

    /**
     * 加入列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/5 19:19
     */
    public function actionJoin()
    {
        $wx = Yii::$app->request->get("wx", "");
        if (empty($wx)) {
            return ToolsHelper::funcReturn("微信号为空");
        }
        $joinList = WechatOwner::find()->where(['wx' => $wx])->asArray()->all();
        return ToolsHelper::funcReturn(
            "加入列表",
            true,
            [
                'joinList' => $joinList
            ]
        );
    }


}