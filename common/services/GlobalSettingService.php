<?php
/**
 * 全局设置服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/18 11:45
 */

namespace common\services;


use common\models\GlobalSetting;

class GlobalSettingService
{
    CONST CRON_BUSINESS_PRODUCT_START_ID = "cron_business_product_start_id";
    /**
     * 保存全局设置
     *
     * @param $key
     * @param $data
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/10/18 11:53
     */
    public static function saveSetting($key, $data)
    {
        if(is_array($data)){
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        $globalSettingModel = GlobalSetting::find()->where(['key'=>$key])->one();
        if(empty($globalSettingModel)){
            $globalSettingModel = new GlobalSetting();
            $globalSettingModel->key = $key;
        }
        $globalSettingModel->value = $data;
        if($globalSettingModel->save()){
            return true;
        }
        return false;
    }

    /**
     * 读取全局设置
     *
     * @param     $key
     *
     * @return false|string|null
     *
     * @author     xudt
     * @date-time  2021/10/18 11:56
     */
    public static function getSetting($key)
    {
        return GlobalSetting::find()->select(['value'])->where(['key'=>$key])->scalar();
    }
}