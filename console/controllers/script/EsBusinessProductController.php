<?php
/**
 * 电商商品ES索引重新创建
 * 使用方法：
 * 1.确定当前索引别名，INDEXNAME2为当前的，INDEXNAME为要换的；
 * 2.执行createIndex方法，生成新索引；
 * 3.执行alias方法，替换索引别名；
 * 4.执行deleteIndex方法，删除旧索引；
 *
 * @author xudt<xudengtang@km.com>
 * @date   : 2020/9/9 09:57
 */

namespace console\controllers\script;

use common\models\BusinessProduct;
use common\models\BusinessProductNew;
use common\models\elasticsearch\EsBusinessProductCopy;
use common\models\ProductCategory;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class EsBusinessProductController extends Controller
{
    const INDEXNAME = "xianzhi_business_product_1"; //当前要换的
    const INDEXNAME2 = "xianzhi_business_product_2"; //当前的

    /**
     * 创建索引
     * 注意当前索引名
     *
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/9 09:59
     */
    public function actionCreateIndex()
    {
        $index = self::INDEXNAME;

        $mapping = EsBusinessProductCopy::mapping();
        $params = [
            'index' => $index,
            'body' => [
                'settings' =>
                    [
                        /*** 此参数增大是为了 减少加入许多document时es服务的压力 ***/
                        'number_of_shards' => 1,
                        'index' => ['refresh_interval' => '5s'],
                        'analysis' => [
                            "analyzer" => [
                                'ik' => [
                                    'tokenizer' => 'ik_max_word',
                                ]
                            ]
                        ]
                    ],
                'mappings' => $mapping
            ]
        ];


        /** @var \Elasticsearch\Client $client */
        $client = Yii::$app->get('es')->getClient();
        try {
            $client->indices()->create($params);
        } catch (\Exception $e) {
            var_export($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * 重新生成Es数据
     *
     * @param  $lastId
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/4/16 17:39
     */
    public function actionInsertData($lastId = 0)
    {
        try {
            $pageSize = 100;
            for ($page = 1; $page > 0; $page++) {
                $start = ($page - 1) * $pageSize;
                $productList = BusinessProductNew::find()->where(['status' => 1])->andWhere(['>=', 'id', $lastId])->offset($start)->limit($pageSize)->asArray()->all();
                if (empty($productList)) {
                    break;
                }


                $categoryIdArr = [];
                foreach ($productList as $value) {
                    if (!empty($value['category'])) {
                        $categoryIdArr[] = $value['category'];
                    }
                }

                $categoryInfoArr = ProductCategory::find()->select(['category_name'])->where(['id' => $categoryIdArr])->indexBy('id')->column();

                $dataList = [];
                foreach ($productList as $key => &$product) {
                    $product['url'] = $product['click_url'];
                    $product['discount_url'] = "";
                    $product['category_id'] = $product['category'];
                    $product['category_name'] = isset($categoryInfoArr[$product['category']]) ? $categoryInfoArr[$product['category']] : '';
                    $product['price'] = floatval($product['price']);
                    $product['cash_back_price'] = floatval($product['cash_back_price']);
                    $dataList[] = $product;
                }
                if (empty($dataList)) {
                    break;
                }
                // 批量写入
                EsBusinessProductCopy::bulk($dataList, 'id');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }


    /**
     * 查询数据
     *
     * @throws \yii\base\InvalidConfigException
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/9 17:09
     */
    public function actionSelect()
    {
        $search = [
            'query' => [
                "match_all" => (object)[]
            ]
        ];

        $params = [
            'index' => self::INDEXNAME,
            'body' => $search
        ];

        /** @var Client $client */
        $client = Yii::$app->get('es')->getClient();
        $response = $client->search($params);
        echo "<pre>";
        print_r($response);
        die;
    }


    /**
     * 替换索引别名
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/1/7 17:59
     */
    public function actionAlias()
    {
        /** @var \Elasticsearch\Client $client */
        $client = Yii::$app->get('es')->getClient();
        $response = $client->indices()->deleteAlias(['index' => self::INDEXNAME2, 'name' => 'xianzhi_business_product']);
        if ($response['acknowledged']) {
            $client->indices()->putAlias(['index' => self::INDEXNAME, 'name' => 'xianzhi_business_product']);
            return ExitCode::OK;
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * 删除索引
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/1/7 17:59
     */
    public function actionDeleteIndex()
    {
        /** @var \Elasticsearch\Client $client */
        $client = Yii::$app->get('es')->getClient();
        $response = $client->indices()->delete(['index' => self::INDEXNAME2]);
        if ($response['acknowledged']) {
            return ExitCode::OK;
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }
}