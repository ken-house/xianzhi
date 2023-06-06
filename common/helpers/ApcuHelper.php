<?php
/**
 * Apcu 助手类
 * @author xudt
 * @date   : 2019/12/7 11:55
 */
namespace common\helpers;

use Yii;

class ApcuHelper
{
    /**
     * 获取apcuKey
     * @author   xudt
     * @dateTime 2019/12/7 11:55
     * @param       $key
     * @param array ...$args
     *
     * @return bool|string
     */
    public static function RK($key, ...$args)
    {
        if(isset(Yii::$app->params['apcu.config'][$key])){
            return sprintf(Yii::$app->params['apcu.config'][$key], ...$args);
        }
        return false;
    }
}