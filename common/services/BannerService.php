<?php
/**
 * banner服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/6/2 11:31
 */

namespace common\services;

use common\helpers\ApcuHelper;
use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Banner;
use Yii;

class BannerService
{
    const BANNER_INDEX = 0;
    const BANNER_HOT = 1;
    const BANNER_FREE = 2;
    const BANNER_CHONGWU = 3;
    const BANNER_ZUFANG = 4;
    const BANNER_JD = 5;
    const BANNER_PDD = 6;
    const BANNER_JOB = 7;
    const BANNER_GROUP_BUY = 8;

    /**
     * 获取有效的banner列表
     *
     * @param     $lat
     * @param     $lng
     * @param int $type 0 首页 1 热门专区 2 免费专区 3 宠物领养  4 房产出租  5 京东 6拼多多 7 兼职 8 团购
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/6/15 16:38
     */
    public function getBannerList($lat, $lng, $type = 0)
    {
        $now = time();
        $bannerIdArr = [];
        // 获取附近的banner对应的id
        if (!empty($lat) && !empty($lng)) {
            $distList = $this->getBannerListFromGeoRedis($lat, $lng, 10);
            if (!empty($distList)) {
                foreach ($distList as $key => $value) {
                    $bannerIdArr[] = $value[0];
                }
                asort($bannerIdArr);
            }
        }

        // apcu 缓存
        $apcuKey = ApcuHelper::RK("bannerList", $type, implode("-", $bannerIdArr));
        $bannerList = apcu_fetch($apcuKey, $exist);
        if (!$exist) {
            $bannerModel = Banner::find()->where(['status' => 1, 'type' => $type]);
            if (!empty($bannerIdArr)) {
                $bannerModel->andWhere(['OR', ['id' => $bannerIdArr], ['lat' => 0]]);
            } else {
                $bannerModel->andWhere(['lat' => 0]);
            }
            $bannerList = $bannerModel->orderBy('sort asc')->asArray()->all();
            apcu_store($apcuKey, $bannerList, 3600);
        }

        // 当前版本号
        $versionNum = Yii::$app->request->headers->get('version-num', 0);
        // todo 兼容
        $bannerList = [];

        if (!empty($bannerList)) {
            foreach ($bannerList as $key => &$value) {
                if ($value['app_id'] == "wx91d27dbf599dff74") { // 京东
                    $value['link_url'] = "/pages/union/proxy/proxy?spreadUrl=" . urlencode($value['link_url']) . "&EA_PTAG=" . JdService::JD_ID;
                }

                if ($value['start_time'] == 0 && $value['end_time'] == 0) { // 不限时间
                    continue;
                }

                if (!($now >= $value['start_time'] && $now <= $value['end_time'])) { // 在时间范围内生效
                    unset($bannerList[$key]);
                }

                if (empty($value['min_version']) || empty($value['max_version'])) {
                    continue;
                }

                if (!($versionNum >= $value['min_version'] && $versionNum <= $value['max_version'])) {
                    unset($bannerList[$key]);
                }
            }
        }
        return array_values($bannerList);
    }

    /**
     * 查询周边的banner
     *
     * @param $lat
     * @param $lng
     * @param $radius
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/6/2 11:53
     */
    private function getBannerListFromGeoRedis($lat, $lng, $radius)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoBanner');
        return $redisClient->georadius($redisKey, $lng, $lat, $radius, 'km', 'WITHDIST');
    }


    /**
     * 保存banner
     *
     * @param array $data
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/27 17:45
     */
    public function save($data = [])
    {
        if (empty($data['id'])) {
            $bannerModel = new Banner();
        } else {
            $bannerModel = Banner::find()->where(['id' => $data['id']])->one();
        }

        $bannerModel->attributes = $data;
        if (!$bannerModel->save()) {
            return ToolsHelper::funcReturn("保存失败");
        }

        // 添加位置到redis
        $this->addGeoData($bannerModel->id, $bannerModel->lat, $bannerModel->lng);

        return ToolsHelper::funcReturn("保存成功", true);
    }

    /**
     * 添加banner的位置信息到geoRedis中
     *
     * @param $id
     * @param $lat
     * @param $lng
     *
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:37
     */
    public function addGeoData($id, $lat, $lng)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoBanner');

        $redisClient->geoadd($redisKey, $lng, $lat, $id);
    }

}