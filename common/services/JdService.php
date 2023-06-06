<?php
/**
 * 京东服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/8 14:13
 */

namespace common\services;

use common\helpers\ToolsHelper;
use common\models\BusinessProductTmp;
use Yii;

class JdService
{
    const SOURCE_ID = 1;
    const JD_ID = "2018521330"; // 京东联盟id
    private $domain = "https://api.jd.com/routerjson";
    private $appKey = "ae24d906ca0487270f20d064de5e727f";
    private $secretKey = "0560b9bc45f74c32bdd5aded7cd2c1f1";
    private $appId = "wx91d27dbf599dff74"; // 京东购物appid

    /**
     * 根据搜索关键词查找京东商品列表
     *
     * @param        $keyword
     * @param int    $page
     * @param int    $pageSize
     * @param string $sortName
     * @param string $sort
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/30 14:37
     */
    public function getProductListByKeyword($keyword, $page = 1, $pageSize = 20, $sortName = "inOrderCount30DaysSku", $sort = "desc")
    {
        $now = time();
        try {
            $buyParamJson = [
                'goodsReqDTO' => [
                    'keyword' => $keyword,
                    'pageIndex' => $page,
                    'pageSize' => $pageSize,
                    'forbidTypes' => '10,11',
                    'sortName' => $sortName,
                    'sort' => $sort,
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.goods.query',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_goods_query_responce']['code']) && $data['jd_union_open_goods_query_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_goods_query_responce']['queryResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    $dataList = $dataArr['data'];

                    // 格式化数据库需要的数据
                    $productList = [];
                    foreach ($dataList as $key => $value) {
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
                        $clickUrl = '';
                        if (!empty($urlArr)) {
                            $clickUrl = isset($urlArr['clickURL']) ? $urlArr['clickURL'] : '';
                        }
                        if (empty($clickUrl)) {
                            continue;
                        }


                        $productData = [];
                        $productData['business_product_id'] = $value['skuId'];
                        $productData['title'] = $value['skuName'];
                        $productData['app_id'] = $this->appId;
                        $productData['click_url'] = $clickUrl;
                        $productData['price'] = floatval($value['priceInfo']['price']);
                        $productData['cash_back_price'] = floatval($value['commissionInfo']['commission']);
                        $productData['comment_num'] = $value['comments'];
                        $productData['sale_num'] = 0;
                        $productData['pics'] = json_encode($imageUrlArr);
                        $productData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                        $productData['shop_name'] = $value['shopInfo']['shopName'];
                        $productData['source_id'] = 1;
                        $productData['search_keyword'] = $keyword;
                        $productData['created_at'] = $now;

                        $productList[] = $productData;
                    }
                    // 写入到临时数据表中
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductTmp::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'source_id', 'search_keyword', 'created_at'], $productList)->execute();
                    }
                    return $productList;
                }

                if (($dataArr['code'] == 200 && empty($dataArr['data'])) || $dataArr['code'] == 402) {
                    return [];
                }
            }
            Yii::info(['func_name' => 'Jd.getProductListByKeyword', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getProductListByKeyword', 'message' => $e->getMessage()], 'trace');
            return [];
        }
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
    public function getPromotionProductUrl($materialUrl, $couponUrl = '')
    {
        try {
            if (empty($materialUrl)) {
                return [];
            }
            if (strpos($materialUrl, 'https') === false) {
                $materialUrl = 'https://' . $materialUrl;
            }

            $buyParamJson = [
                'promotionCodeReq' => [
                    'materialId' => $materialUrl,
                    'couponUrl' => $couponUrl,
                    'chainType' => 3,
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.promotion.bysubunionid.get',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_promotion_bysubunionid_get_responce']['code']) && $data['jd_union_open_promotion_bysubunionid_get_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    return $dataArr['data'];
                }
                if (in_array($dataArr['code'], [2001911])) {
                    return [];
                }
            }
            Yii::info(['func_name' => 'Jd.getPromotionProductUrl', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getPromotionProductUrl', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 商品推荐
     *
     * @param int    $eliteId  频道ID:1-好券商品,2-精选卖场,10-9.9包邮,15-京东配送,22-实时热销榜,23-为你推荐,24-数码家电,25-超市,26-母婴玩具,27-家具日用,28-美妆穿搭,30-图书文具,31-今日必推,32-京东好物,33-京东秒杀,34-拼购商品,40-高收益榜,41-自营热卖榜,108-秒杀进行中,109-新品首发,110-自营,112-京东爆品,125-首购商品,129-高佣榜单,130-视频商品,153-历史最低价商品榜,210-极速版商品,238-新人价商品,247-京喜9.9,249-京喜秒杀,315-秒杀未开始,340-时尚趋势品,341-3C新品,342-智能新品,343-3C长尾商品,345-时尚新品,346-时尚爆品,1001-选品库
     * @param int    $page
     * @param int    $pageSize 最大50
     * @param string $sortName
     * @param string $sort
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 17:01
     */
    public function getRecommendProductList($eliteId = 1, $page = 1, $pageSize = 30, $sortName = "inOrderCount30DaysSku", $sort = "desc")
    {
        $now = time();
        try {
            $buyParamJson = [
                'goodsReq' => [
                    'eliteId' => $eliteId,
                    'pageIndex' => $page,
                    'pageSize' => $pageSize,
                    'forbidTypes' => "10,11",
                    'sortName' => $sortName,
                    'sort' => $sort,
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.goods.jingfen.query',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_goods_jingfen_query_responce']['code']) && $data['jd_union_open_goods_jingfen_query_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_goods_jingfen_query_responce']['queryResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    $dataList = $dataArr['data'];

                    // 格式化数据库需要的数据
                    $productList = [];
                    foreach ($dataList as $key => $value) {
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
                        $clickUrl = '';
                        if (!empty($urlArr)) {
                            $clickUrl = isset($urlArr['clickURL']) ? $urlArr['clickURL'] : '';
                        }
                        if (empty($clickUrl)) {
                            continue;
                        }


                        $productData = [];
                        $productData['business_product_id'] = $value['skuId'];
                        $productData['title'] = $value['skuName'];
                        $productData['app_id'] = $this->appId;
                        $productData['click_url'] = $clickUrl;
                        $productData['price'] = floatval($value['priceInfo']['price']);
                        $productData['cash_back_price'] = floatval($value['commissionInfo']['commission']);
                        $productData['comment_num'] = $value['comments'];
                        $productData['sale_num'] = 0;
                        $productData['pics'] = json_encode($imageUrlArr);
                        $productData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                        $productData['shop_name'] = $value['shopInfo']['shopName'];
                        $productData['source_id'] = 1;
                        $productData['search_keyword'] = !empty($value['categoryInfo']['cid3Name']) ? $value['categoryInfo']['cid3Name'] : '';
                        $productData['created_at'] = $now;

                        $productList[] = $productData;
                    }
                    // 写入到临时数据表中
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductTmp::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'source_id', 'search_keyword', 'created_at'], $productList)->execute();
                    }
                    return $productList;
                }

                if ($dataArr['code'] == 202) {
                    return [];
                }
            }
            Yii::info(['func_name' => 'Jd.getRecommendProductList', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getRecommendProductList', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 获取商品详情
     *
     * @param $businessProductIdArr
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/25 15:52
     */
    public function getProductInfo($businessProductIdArr = [])
    {
        try {
            $buyParamJson = [
                'goodsReq' => [
                    'skuIds' => $businessProductIdArr,
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.goods.bigfield.query',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_goods_bigfield_query_responce']['code']) && $data['jd_union_open_goods_bigfield_query_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_goods_bigfield_query_responce']['queryResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    return $dataArr['data'];
                }
                if ($dataArr['code'] == 200 && empty($dataArr['data'])) {
                    return [];
                }
            }
            Yii::info(['func_name' => 'Jd.getProductInfo', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getProductInfo', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 猜你喜欢
     *
     * @param int $eliteId 频道ID：1.猜你喜欢、2.实时热销、3.大额券、4.9.9包邮
     * @param int $page
     * @param int $pageSize
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 17:01
     */
    public function getGuessProductList($eliteId = 1, $page = 1, $pageSize = 10)
    {
        $now = time();
        try {
            $buyParamJson = [
                'goodsReq' => [
                    'eliteId' => $eliteId,
                    'pageIndex' => $page,
                    'pageSize' => $pageSize,
                    'forbidTypes' => "10,11",
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.goods.material.query',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_goods_material_query_responce']['code']) && $data['jd_union_open_goods_material_query_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_goods_material_query_responce']['queryResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    $dataList = $dataArr['data'];

                    // 格式化数据库需要的数据
                    $productList = [];
                    foreach ($dataList as $key => $value) {
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
                        $clickUrl = '';
                        if (!empty($urlArr)) {
                            $clickUrl = isset($urlArr['clickURL']) ? $urlArr['clickURL'] : '';
                        }
                        if (empty($clickUrl)) {
                            continue;
                        }


                        $productData = [];
                        $productData['business_product_id'] = $value['skuId'];
                        $productData['title'] = $value['skuName'];
                        $productData['app_id'] = $this->appId;
                        $productData['click_url'] = $clickUrl;
                        $productData['price'] = floatval($value['priceInfo']['price']);
                        $productData['cash_back_price'] = floatval($value['commissionInfo']['commission']);
                        $productData['comment_num'] = $value['comments'];
                        $productData['sale_num'] = 0;
                        $productData['pics'] = json_encode($imageUrlArr);
                        $productData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                        $productData['shop_name'] = $value['shopInfo']['shopName'];
                        $productData['source_id'] = 1;
                        $productData['search_keyword'] = !empty($value['categoryInfo']['cid3Name']) ? $value['categoryInfo']['cid3Name'] : '';
                        $productData['created_at'] = $now;

                        $productList[] = $productData;
                    }
                    // 写入到临时数据表中
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductTmp::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'source_id', 'search_keyword', 'created_at'], $productList)->execute();
                    }
                    return $productList;
                }
            }
            Yii::info(['func_name' => 'Jd.getGuessProductList', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getGuessProductList', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 可按查询方式查询1小时内的订单
     *
     * @param int    $type 订单时间查询类型(1：下单时间，2：完成时间（购买用户确认收货时间），3：更新时间
     * @param string $endTime
     * @param int    $page
     * @param int    $pageSize
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 18:09
     */
    public function getOrderListByType($type, $endTime, $page = 1, $pageSize = 50)
    {
        $startTime = date("Y-m-d H:i:s", strtotime($endTime) - 3600);
        try {
            $buyParamJson = [
                'orderReq' => [
                    'pageIndex' => $page,
                    'pageSize' => $pageSize,
                    'type' => $type,
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                ]
            ];

            // 获取签名
            $paramArr = [
                'timestamp' => date("Y-m-d H:i:s"),
                'method' => 'jd.union.open.order.row.query',
                'app_key' => $this->appKey,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5',
                '360buy_param_json' => json_encode($buyParamJson),
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $url = $this->domain . "?" . http_build_query($paramArr);
            $data = ToolsHelper::sendRequest($url, "GET");
            if (isset($data['jd_union_open_order_row_query_responce']['code']) && $data['jd_union_open_order_row_query_responce']['code'] == 0) {
                $queryResult = $data['jd_union_open_order_row_query_responce']['queryResult'];
                $dataArr = json_decode($queryResult, true);
                if ($dataArr['code'] == 200 && !empty($dataArr['data'])) {
                    return $dataArr['data'];
                }
                if ($dataArr['code'] == 200 && empty($dataArr['data'])) {
                    return [];
                }
            }
            Yii::info(['func_name' => 'Jd.getOrderListByType', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Jd.getOrderListByType', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 获取接口签名
     *
     * @param array $paramArr
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/10/8 14:24
     */
    private function getSignature($paramArr = [])
    {
        ksort($paramArr);
        $string = "";
        foreach ($paramArr as $key => $value) {
            $string .= $key . $value;
        }
        $string = $this->secretKey . $string . $this->secretKey;
        return strtoupper(md5($string));
    }
}