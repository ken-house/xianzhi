<?php
/**
 * @author xudt
 * @date   : 2019/11/2 11:01
 */

namespace backend\modules\api\controllers;

use common\helpers\ToolsHelper;
use yii\base\Controller;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;

use Yii;
use yii\web\Response;

class BaseController extends Controller
{
    public function behaviors()
    {
        return ArrayHelper::merge(
            [
                [
                    'class' => Cors::className(),
                    'cors' => [
                        'Origin' => ['*'],
                    ],
                ],
            ],
            parent::behaviors()
        );
    }

    /**
     * 控制器前置方法
     *
     * @param \yii\base\Action $action
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/25 15:17
     *
     */
    public function beforeAction($action)
    {
        return parent::beforeAction($action);
    }


    /**
     * 控制器后置方法
     *
     * @param \yii\base\Action $action
     * @param mixed            $result
     *
     * @return array|mixed|string
     * @author   xudt
     * @dateTime 2019/11/25 15:17
     *
     */
    public function afterAction($action, $result)
    {
        if (!is_array($result)) {
            return $result;
        }
        $this->response->format = "json";
        $result = parent::afterAction($action, $result);
        return ToolsHelper::intToString($result);
    }
}