<?php
/**
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/9/30 13:29
 */

namespace console\controllers\cron;

use common\helpers\ToolsHelper;
use common\models\BusinessProductNew;
use common\models\ProductCategory;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class JdProductController extends Controller
{
    private $url = "https://api.jd.com/routerjson";
    private $appkey = "ae24d906ca0487270f20d064de5e727f";
    private $secretKey = "0560b9bc45f74c32bdd5aded7cd2c1f1";

    /**
     * 调用京东接口，写入商品列表到数据库中
     *
     * @param     $keyword
     * @param int $categoryId
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/10/4 17:57
     */
    public function actionProductList($keyword, $categoryId = 0)
    {
        try {
            $categoryName = ProductCategory::find()->select(['category_name'])->where(['id'=>$categoryId])->scalar();
            var_dump($categoryId, $keyword);
            // 根据关键词搜索，调用京东接口
            $insertList = [];
            for ($page = 1; $page <= 5; $page++) {
                $productList = $this->getProductListByKeyword($categoryId, $categoryName, $page);
                if (empty($productList)) {
                    break;
                }
                $insertList = array_merge($insertList, $productList);
            }
            var_dump(count($insertList));

            // 批量插入数据库中
            if (!empty($insertList)) {
                $row = Yii::$app->db->createCommand()->batchInsert(BusinessProductNew::tableName(), ['business_product_id', 'title', 'short_url', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'category', 'source_id', 'status', 'updated_at', 'created_at'], $insertList)->execute();
                var_dump($row);
                if (!$row) {
                    Yii::info(['category_id' => $categoryId, 'message' => '写入数据库失败'], 'trace');
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            Yii::info($e->getMessage(), 'trace');
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }


    /**
     * 根据搜索关键词查找京东商品列表
     *
     * @param     $categoryId
     * @param     $categoryName
     * @param int $pageIndex
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/30 14:37
     */
    private function getProductListByKeyword($categoryId, $categoryName, $pageIndex = 1)
    {
        $timestamp = date("Y-m-d H:i:s");
        $buyParamJson = [
            'goodsReqDTO' => [
                'keyword' => $categoryName,
                'pageIndex' => $pageIndex,
                'forbidTypes' => '10,11',
                'pageSize' => 30,
            ]
        ];

        // 获取签名
        $paramArr = [
            'timestamp' => $timestamp,
            'method' => 'jd.union.open.goods.query',
            'app_key' => $this->appkey,
            'format' => 'json',
            'v' => '1.0',
            'sign_method' => 'md5',
            '360buy_param_json' => json_encode($buyParamJson),
        ];
        $paramArr2 = $paramArr;
        ksort($paramArr2);
        $string = "";
        foreach ($paramArr2 as $key => $value) {
            $string .= $key . $value;
        }
        $string = $this->secretKey . $string . $this->secretKey;
        $paramArr['sign'] = strtoupper(md5($string));
        $url = $this->url . "?" . http_build_query($paramArr);
        $data = ToolsHelper::sendRequest($url, "GET");
        if (!empty($data) && $data['jd_union_open_goods_query_responce']['code'] == 0) {
            $queryResult = $data['jd_union_open_goods_query_responce']['queryResult'];
            $dataArr = json_decode($queryResult, true);
            if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                $dataList = $dataArr['data'];

                // 格式化数据库需要的数据
                $insertList = [];
                foreach ($dataList as $key => $value) {
                    // 检测是否存在
                    $exist = BusinessProductNew::find()->where(['business_product_id' => $value['skuId']])->exists();
                    if ($exist) {
                        continue;
                    }

                    // 是否自营
                    $tagArr = [];
                    if ($value['owner'] == "g") {
                        $tagArr[] = "自营";
                    }

                    // 是否京东配送
                    if (!empty($value['deliveryType'])) {
                        $tagArr[] = "京配";
                    }

                    // 是否有券
                    if (!empty($value['couponInfo']['couponList'])) {
                        $tagArr[] = "券";
                    }

                    // 无理由退货
                    if (!empty($value['skuLabelInfo']['is7ToReturn'])) {
                        $tagArr[] = "无理由退货";
                    }


                    // 图片地址
                    $imageUrlArr = [];
                    $imageList = $value['imageInfo']['imageList'];
                    if (!empty($imageList)) {
                        $i = 0;
                        foreach ($imageList as $v) {
                            if ($i >= 9) {
                                break;
                            }
                            $imageUrlArr[] = $v['url'];
                            $i++;
                        }
                    }

                    // 获取推广地址
                    $couponUrlLink = !empty($value['couponInfo']['couponList'][0]['link']) ? $value['couponInfo']['couponList'][0]['link'] : '';
                    $urlArr = $this->getPromotionProductUrl($value['materialUrl'], $couponUrlLink);
                    $status = 0;
                    $shortUrl = $clickUrl = '';
                    if (!empty($urlArr)) {
                        $status = 1;
                        $shortUrl = isset($urlArr['shortURL']) ? $urlArr['shortURL'] : '';
                        $clickUrl = isset($urlArr['clickURL']) ? $urlArr['clickURL'] : '';
                    }

                    $insertData = [];
                    $insertData['business_product_id'] = $value['skuId'];
                    $insertData['title'] = $value['skuName'];
                    $insertData['short_url'] = $shortUrl;
                    $insertData['click_url'] = $clickUrl;
                    $insertData['price'] = floatval($value['priceInfo']['price']);
                    $insertData['cache_back_price'] = floatval($value['commissionInfo']['commission']);
                    $insertData['comment_num'] = $value['comments'];
                    $insertData['sale_num'] = 0;
                    $insertData['pics'] = json_encode($imageUrlArr);
                    $insertData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                    $insertData['shop_name'] = $value['shopInfo']['shopName'];
                    $insertData['category'] = $categoryId;
                    $insertData['source_id'] = 1;
                    $insertData['status'] = $status;
                    $insertData['updated_at'] = time();
                    $insertData['created_at'] = time();

                    $insertList[] = $insertData;
                }
                return $insertList;
            }
        }
        // 记录错误信息
        Yii::info(['category_name' => $categoryName, 'pageIndex' => $pageIndex, 'data' => $data], 'trace');
        return [];
    }

    /**
     * 获取推广地址
     *
     * @param        $materialUrl
     * @param string $couponUrl
     *
     * @return array|mixed|string
     *
     * @author     xudt
     * @date-time  2021/9/30 15:13
     */
    private function getPromotionProductUrl($materialUrl, $couponUrl = '')
    {
        if (empty($materialUrl)) {
            return '';
        }
        if (strpos($materialUrl, 'https') === false) {
            $materialUrl = 'https://' . $materialUrl;
        }

        $timestamp = date("Y-m-d H:i:s");
        $buyParamJson = [
            'promotionCodeReq' => [
                'materialId' => $materialUrl,
                'couponUrl' => $couponUrl,
                'chainType' => 3,
            ]
        ];

        // 获取签名
        $paramArr = [
            'timestamp' => $timestamp,
            'method' => 'jd.union.open.promotion.bysubunionid.get',
            'app_key' => $this->appkey,
            'format' => 'json',
            'v' => '1.0',
            'sign_method' => 'md5',
            '360buy_param_json' => json_encode($buyParamJson),
        ];
        $paramArr2 = $paramArr;
        ksort($paramArr2);
        $string = "";
        foreach ($paramArr2 as $key => $value) {
            $string .= $key . $value;
        }
        $string = $this->secretKey . $string . $this->secretKey;
        $paramArr['sign'] = strtoupper(md5($string));
        $url = $this->url . "?" . http_build_query($paramArr);
        $data = ToolsHelper::sendRequest($url, "GET");
        if (!empty($data) && $data['jd_union_open_promotion_bysubunionid_get_responce']['code'] == 0) {
            $queryResult = $data['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
            $dataArr = json_decode($queryResult, true);
            if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                return $dataArr['data'];
            }
        }
        return [];
    }

    /**
     * 去重
     *
     *
     * @author     xudt
     * @date-time  2021/10/1 17:54
     */
    public function actionUnique()
    {
        $dataList = BusinessProductNew::find()->select(['business_product_id', 'count(*) c'])->groupBy("business_product_id")->having("c>=2")->asArray()->all();
        if (!empty($dataList)) {
            foreach ($dataList as $key => $value) {
                $businessProductId = $value['business_product_id'];
                $limit = $value['c'] - 1;
                $idArr = BusinessProductNew::find()->select(['id'])->where(['business_product_id' => $businessProductId])->limit($limit)->orderBy("id asc")->column();
                BusinessProductNew::deleteAll(['id' => $idArr]);
            }
        }
    }
}