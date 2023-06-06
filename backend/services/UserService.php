<?php
/**
 * 用户管理
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/12/4 14:54
 */
namespace backend\services;

use common\helpers\ToolsHelper;
use common\models\User;

class UserService
{
    /**
     * 用户列表
     *
     * @param $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/4 14:57
     */
    public function getUserList($data)
    {
        $userModel = User::find()->andFilterWhere(['LIKE', 'nickname', $data['keyword']]);
        if (!empty($data['id'])) {
            $userModel->andWhere(['id' => $data['id']]);
        }
        if ($data['status'] != -1) {
            $userModel->andWhere(['status' => $data['status']]);
        }
        $userCountModel = clone $userModel;
        $count = $userCountModel->count();

        $start = ($data['page'] - 1) * $data['page_size'];
        $list = $userCountModel->offset($start)->limit($data['page_size'])->orderBy('id desc')->asArray()->all();
        return ToolsHelper::funcReturn(
            "用户列表",
            true,
            [
                'list' => $list,
                'count' => $count,
                'page' => $data['page'],
            ]
        );
    }
}