<?php
/**
 * 修复数据脚本
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/6/17 22:03
 */

namespace console\controllers\script;

use common\helpers\RedisHelper;
use common\models\mongo\MongodbRewardPointRecord;
use common\models\Product;
use common\models\User;
use common\models\UserData;
use common\services\ProductService as CommonProductService;
use common\services\RewardPointService;
use common\services\UserService;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class FixBugController extends Controller
{
    /**
     * 修复商品地址位置数据
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/6/17 22:10
     */
    public function actionProductPosition()
    {
        $data = Product::find()->select(['id', 'lat', 'lng'])->where(['>=', 'id', 1504])->andWhere(['status' => 1])->asArray()->all();
        foreach ($data as $key => $value) {
            //写入geoRedis
            $commonProductService = new CommonProductService();
            $commonProductService->addProductGeoData($value['id'], $value['lat'], $value['lng']);
        }
        return ExitCode::OK;
    }

    public function actionUserData()
    {
        $userIdArr = [
            "100668",
            "101334",
            "101317",
            "101208",
            "101338",
            "101274",
            "101120",
            "101027",
            "101340",
            "101341",
            "101342",
            "100967",
            "101344",
            "101346",
            "101050",
            "100345",
            "100982",
            "100212",
            "100763",
            "100909",
            "101352",
            "101353",
            "101356",
            "100891",
            "101358",
            "101363",
            "101372",
            "100000",
            "100841",
            "100497",
            "101359",
            "100471",
            "101381",
            "101364",
            "101383",
            "101057",
        ];

        foreach ($userIdArr as $uid) {
            $recordList = RewardPointService::getRewardPointRecord($uid, 0, 1, 500);
            if (!empty($recordList)) {
                $currentPoint = 0;
                foreach ($recordList as $key => $value) {
                    $id = $value['_id'];
                    $point = $value['point'];

                    $currentPoint += $point;

                    // 更新mongo
                    MongodbRewardPointRecord::resetTableName($uid);
                    MongodbRewardPointRecord::updateAll(['current_point' => $currentPoint], ['_id' => $id]);
                }

                // 数据库数据修改
                $res = UserData::updateAll(['reward_point' => $currentPoint], ['uid' => $uid]);

                // redis数据
                $userService = new UserService();
                $res2 = $userService->updateStructureDataToUserInfoRedis($uid, ['reward_point' => $currentPoint]);
            }
        }
    }
}