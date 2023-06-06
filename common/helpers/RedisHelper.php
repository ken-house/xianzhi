<?php
/**
 * Redis对象助手类
 */
namespace common\helpers;

use Yii;

class RedisHelper
{
    /**
     * 获取redis的配置，并返回所需要的key的全名
     * @author   xudt
     * @dateTime 2019/11/7 17:58
     * @param       $key
     * @param array ...$args
     *
     * @return bool|string
     */
    public static function RK($key, ...$args)
    {
        if(isset(Yii::$app->params['redis.config'][$key]['key'])){
            return sprintf(Yii::$app->params['redis.config'][$key]['key'], ...$args);
        }
        return false;
    }

    /**
     * 获取redis的配置，并返回所需要的存储在redis的结构的真实名称
     * @author   xudt
     * @dateTime 2019/11/7 17:58
     * @param        $key
     * @param string $structure
     *
     * @return bool
     */
    public static function RS($key, $structure = '')
    {
        if(!empty($structure)){
            if(isset(Yii::$app->params['redis.config'][$key]['structure'][$structure])){
                return Yii::$app->params['redis.config'][$key]['structure'][$structure];
            }
        }else{
            if(isset(Yii::$app->params['redis.config'][$key])){
                return Yii::$app->params['redis.config'][$key]['structure'];
            }
        }

        return false;
    }


    /**
     * 获取redis的配置，然后组合填充数据，并返回组合好的数据
     * @author   xudt
     * @dateTime 2019/11/7 17:58
     * @param $key
     * @param $data
     *
     * @return array|bool
     */
    public static function RF($key, $data)
    {
        $return = array();

        if(is_array($data)){
            if(isset(Yii::$app->params['redis.config'][$key]['structure'])){
                $structure = Yii::$app->params['redis.config'][$key]['structure'];
                foreach ($data as $k => $v){
                    if(isset($structure[$k])){
                        $return[$structure[$k]] = $v;
                    }
                }

                return $return;
            }
        }

        return false;
    }

    /**
     * 获取某个redis配置下的所有structure的名称
     * @author   xudt
     * @dateTime 2019/11/7 17:59
     * @param      $key
     * @param bool $isVal
     *
     * @return array|bool
     */
    public static function RGS($key, $isVal = false)
    {
        $return = [];

        if(isset(Yii::$app->params['redis.config'][$key]['structure'])){
            $structure = Yii::$app->params['redis.config'][$key]['structure'];
            foreach ($structure as $k => $v){
                if($isVal){
                    $return[] = $v;
                }else{
                    $return[] = $k;
                }
            }
            return $return;
        }

        return false;
    }

    /**
     * 将生成的缩写structure，转换成完成的
     * @author   xudt
     * @dateTime 2019/11/7 17:59
     * @param $key
     * @param $data
     *
     * @return array
     */
    public static function RFD($key, $data)
    {
        $return = [];

        if(isset(Yii::$app->params['redis.config'][$key]['structure'])){
            $structure = Yii::$app->params['redis.config'][$key]['structure'];
            foreach ($structure as $k => $v){
                foreach ($data as $k1 => $v1){
                    if($k1 == $v){
                        $return[$k] = $v1;
                        continue;
                    }
                }
            }
        }

        return $return;
    }
}