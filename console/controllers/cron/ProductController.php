<?php
/**
 * 商品相关
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/10 21:05
 */

namespace console\controllers\cron;

use common\helpers\RedisHelper;
use common\models\BusinessProduct;
use common\models\elasticsearch\EsBusinessProduct;
use common\models\elasticsearch\EsProduct;
use common\models\Product;
use common\models\Stick;
use common\services\BusinessProductService;
use common\services\ProductService;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class ProductController extends Controller
{
    /**
     * 功能：同步redis存储的商品数据到数据库、ES中
     * 执行时间：每天凌晨0点执行
     *
     * @author     xudt
     * @date-time  2021/5/10 21:06
     */
    public function actionSyncData()
    {
        try {
            $limit = 1000;
            $lastId = 0;
            $productService = new ProductService();
            do {
                $productList = Product::find()->where(['>', 'id', $lastId])->orderBy("id asc")->asArray()->limit($limit)->all();
                if (!empty($productList)) {
                    foreach ($productList as $key => $value) {
                        $productId = $value['id'];
                        $lastId = $productId;
                        // 读取redis存储的数据
                        $productData = $productService->getProductData($productId);
                        if (empty($productData)) {
                            continue;
                        }

                        // 更新到数据库
                        Product::updateAll($productData, ['id' => $productId]);

                        // 更新到ES
                        EsProduct::update($productId, $productData);
                    }
                }
            } while (!empty($productList));

            return ExitCode::OK;
        } catch (\Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 功能：检测置顶商品是否过期
     * 执行时间：每天凌晨0点执行
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/6/22 17:51
     */
    public function actionStickData()
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');

        $now = time();
        $stickList = Stick::find()->where(['status' => 1])->asArray()->all();
        if (!empty($stickList)) {
            foreach ($stickList as $key => $value) {
                if ($value['end_time'] <= $now) { // 已过期
                    // 更改数据库
                    if (Stick::updateAll(['status' => 0, 'updated_at' => $now], ['id' => $value['id']])) {
                        // 更改redis
                        $redisKey = RedisHelper::RK('distGeoStick', $value['type'], $value['activity_id']);
                        $redisClient->zrem($redisKey, $value['product_id']);
                    }
                }
            }
        }
        return ExitCode::OK;
    }


    /**
     * 更新电商商品点击量
     * 执行时间：每天凌晨2点执行
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/22 10:59
     */
    public function actionSyncBusinessData()
    {
        try {
            $limit = 1000;
            $lastId = 0;
            do {
                $businessProductList = BusinessProduct::find()->where(['>', 'id', $lastId])->andWhere(['status' => 1])->andWhere(['>', 'click_num', 0])->orderBy("id asc")->asArray()->limit($limit)->all();
                if (!empty($businessProductList)) {
                    foreach ($businessProductList as $key => $value) {
                        $productId = $value['id'];
                        $lastId = $productId;

                        // 更新到ES
                        EsBusinessProduct::update($productId, ['click_num' => $value['click_num']]);
                    }
                }
            } while (!empty($businessProductList));

            return ExitCode::OK;
        } catch (\Exception $e) {
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}