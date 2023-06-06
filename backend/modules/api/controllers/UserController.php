<?php
/**
 * 用户管理
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/12/4 14:37
 */
namespace backend\modules\api\controllers;

use backend\services\UserService;
use Yii;

class UserController extends BaseController
{
    /**
     * 用户列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/4 14:57
     */
    public function actionList()
    {
        $data['id'] = Yii::$app->request->get('id', 0);
        $data['keyword'] = Yii::$app->request->get('keyword', "");
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $userService = new UserService();
        $result = $userService->getUserList($data);
        return $result;
    }

}