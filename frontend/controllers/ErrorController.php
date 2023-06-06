<?php
/**
 * 自定义错误页面
 */

namespace frontend\controllers;

class ErrorController extends \yii\web\ErrorAction
{
    /**
     * 错误展示页面 - h5
     *
     */
    protected function renderHtmlResponse()
    {
        return $this->controller->renderPartial('error');
    }

    /**
     * ajax请求错误返回
     *
     */
    protected function renderAjaxResponse()
    {
        return ['errorCode' => 44010101];
    }
}