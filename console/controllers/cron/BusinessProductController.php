<?php
/**
 * 电商商品
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/18 12:01
 */
namespace console\controllers\cron;

use common\models\BusinessProductList;
use common\models\BusinessProductTmp;
use common\services\GlobalSettingService;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class BusinessProductController extends Controller
{
    /**
     * 同步电商商品数据到正式表
     * 执行时间：5分钟执行一次
     *
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/16 08:42
     */
    public function actionSync()
    {
        $id = GlobalSettingService::getSetting(GlobalSettingService::CRON_BUSINESS_PRODUCT_START_ID);
        if (empty($id)) {
            $id = 0;
        }
        try {
            $length = 10000;
            do {
                $productList = BusinessProductTmp::find()->where(['>', 'id', $id])->limit($length)->orderBy('id asc')->asArray()->all();
                $productListCopy = $productList;
                if (!empty($productList)) {
                    $uniqeIdArr = [];
                    foreach ($productList as $key => &$value) {
                        $id = $value['id'];

                        if (in_array($value['business_product_id'], $uniqeIdArr)) {
                            unset($productList[$key]);
                            continue;
                        }
                        // 判断是否存在正式表
                        $exist = BusinessProductList::find()->where(['business_product_id' => $value['business_product_id'], 'source_id' => $value['source_id']])->limit(1)->exists();
                        if ($exist) {
                            unset($productList[$key]);
                            continue;
                        }
                        $uniqeIdArr[] = $value['business_product_id'];
                        unset($value['id']);
                    }

                    // 批量写入数据库
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductList::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'search_keyword', 'source_id', 'created_at'], $productList)->execute();
                    }
                    sleep(10);
                }
                var_dump($id);
                // 记录id到redis作为游标
                GlobalSettingService::saveSetting(GlobalSettingService::CRON_BUSINESS_PRODUCT_START_ID, $id);
            } while (!empty($productListCopy));
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info($e->getMessage(), 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}