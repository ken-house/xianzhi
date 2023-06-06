<?php
/**
 * 商品审核
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/28 10:53
 */

namespace backend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\models\Admin;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

class LoginController extends BaseController
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
                    'only' => ['login', 'logout'],
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['login'],
                            'roles' => ['?'],
                        ],
                        [
                            'allow' => true,
                            'actions' => ['logout'],
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'login' => ['post'],
                        'logout' => ['post'],
                    ],
                ],
            ],
            parent::behaviors()
        );
    }

    /**
     * 管理员登录
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/7 17:22
     */
    public function actionLogin()
    {
        $username = Yii::$app->request->post('username');
        $password = Yii::$app->request->post('password');
        if (empty($username) || empty($password)) {
            return ToolsHelper::funcReturn("用户名或密码为空");
        }
        $identity = Admin::findOne(['username' => $username]);
        if (empty($identity)) {
            return ToolsHelper::funcReturn("帐号不存在");
        }
        if ($identity['status'] != 1) {
            return ToolsHelper::funcReturn("帐号已禁用");
        }
        if ($identity['password'] != $password) {
            return ToolsHelper::funcReturn("密码错误");
        }
        if (!Yii::$app->user->login($identity)) {
            return ToolsHelper::funcReturn("登录失败");
        }
        $isGuest = Yii::$app->user->isGuest;
        return ToolsHelper::funcReturn("登录成功", true, ['id' => Yii::$app->user->id,'isGuest'=>$isGuest]);
    }

    /**
     * 管理员退出登录
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/7 17:23
     */
    public function actionLogout()
    {
        if (!Yii::$app->user->logout()) {
            return ToolsHelper::funcReturn("退出登录失败");
        }
        return ToolsHelper::funcReturn("退出登录成功", true);
    }

}