<?php
/**
 * 拼多多服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/8 17:20
 */

namespace common\services;

use common\helpers\ToolsHelper;
use common\models\BusinessProductTmp;
use Yii;

class PddService
{
    const SOURCE_ID = 2;
    private $domain = "https://gw-api.pinduoduo.com/api/router";
    private $clientId = "dd04f8208c654f03b65cd12829183fc7";
    private $clientKey = "fa5e6374ea27ff9c6874eaf242c78a6d7d763687";
    private $pid = "25207992_220301171"; // 推广位
    private $duoId = 25207992; // 拼多多ID

    /**
     * 根据搜索关键词查找拼多多商品列表
     *
     * @param     $keyword
     * @param int $page
     * @param int $pageSize
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/30 14:37
     */
    public function getProductListByKeyword($keyword = "", $page = 1, $pageSize = 20)
    {
        $now = time();
        try {
            $paramArr = [
                'timestamp' => $now,
                'type' => 'pdd.ddk.goods.search',
                'client_id' => $this->clientId,
                'data_type' => 'JSON',
                'block_cat_packages' => json_encode([1, 2, 3, 4, 5]),
                'pid' => $this->pid,
                'keyword' => $keyword,
                'page' => $page,
                'page_size' => $pageSize
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $data = ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
            if (!empty($data) && isset($data['goods_search_response'])) {
                $productList = [];
                $goodsList = $data['goods_search_response']['goods_list'];
                if (!empty($goodsList)) {
                    // 获取商品id
                    $goodsSignList = [];
                    foreach ($goodsList as $goods) {
                        $goodsSignList[] = $goods['goods_sign'];
                    }

                    // 调用获取推广地址接口
                    $productUrlList = $this->getPromotionProductUrl($goodsSignList);

                    foreach ($goodsList as $key => $value) {
                        // 标签
                        $tagArr = [];
                        foreach ($value['unified_tags'] as $tag) {
                            if (!empty($tag)) {
                                $tagArr[] = $tag;
                            }
                        }

                        $saleNum = $value['sales_tip'];
                        if (strpos($saleNum, "万") !== false) {
                            $saleNum = intval($saleNum) * 10000;
                        }

                        // 价格
                        if (isset($value['min_group_price'])) {
                            $price = floatval($value['min_group_price'] / 100);
                        } else {
                            $price = floatval($value['min_normal_price'] / 100);
                        }

                        // 佣金
                        $cashBackPrice = round(intval($price) * $value['promotion_rate'] / 1000, 2);

                        // 推广地址
                        if (empty($productUrlList[$key]['we_app_info']['page_path'])) {
                            continue;
                        }
                        $clickUrl = $productUrlList[$key]['we_app_info']['page_path'];
                        $appId = $productUrlList[$key]['we_app_info']['app_id'];

                        // 图片地址
                        $imageUrlArr = [$value['goods_image_url']];

                        $productData = [];
                        $productData['business_product_id'] = $value['goods_id'];
                        $productData['title'] = $value['goods_name'];
                        $productData['app_id'] = $appId;
                        $productData['click_url'] = $clickUrl;
                        $productData['price'] = $price;
                        $productData['cash_back_price'] = $cashBackPrice;
                        $productData['comment_num'] = 0;
                        $productData['sale_num'] = intval($saleNum);
                        $productData['pics'] = json_encode($imageUrlArr);
                        $productData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                        $productData['shop_name'] = $value['mall_name'];
                        $productData['source_id'] = 2;
                        $productData['search_keyword'] = $keyword;
                        $productData['created_at'] = $now;

                        $productList[] = $productData;
                    }
                    // 写入到临时数据表中
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductTmp::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'source_id', 'search_keyword', 'created_at'], $productList)->execute();
                    }
                }
                return $productList;
            }
            Yii::info(['func_name' => 'Pdd.getProductListByKeyword', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Pdd.getProductListByKeyword', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 获取拼多多商品推广地址
     *
     * @param array $goodsSignList
     *
     * @return array|string
     *
     * @author     xudt
     * @date-time  2021/10/9 11:29
     */
    public function getPromotionProductUrl($goodsSignList = [])
    {
        try {
            $paramArr = [
                'timestamp' => time(),
                'type' => 'pdd.ddk.goods.promotion.url.generate',
                'client_id' => $this->clientId,
                'data_type' => 'JSON',
                'p_id' => $this->pid,
                'goods_sign_list' => json_encode($goodsSignList),
                'zs_duo_id' => $this->duoId,
                'generate_we_app' => "true",
                'generate_qq_app' => "true",
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $data = ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
            if (!empty($data) && isset($data['goods_promotion_url_generate_response'])) {
                return $data['goods_promotion_url_generate_response']['goods_promotion_url_list'];
            }
            Yii::info(['func_name' => 'Pdd.getPromotionProductUrl', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Pdd.getPromotionProductUrl', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 获取活动频道推广地址
     *
     * @param $resourceType //频道来源：4-限时秒杀,39997-充值中心, 39998-活动转链，39996-百亿补贴，39999-电器城，40000-领券中心，50005-火车票
     * @param $url
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/20 16:16
     */
    public function getPromotionActivityUrl($resourceType, $url)
    {
        try {
            $paramArr = [
                'timestamp' => time(),
                'type' => 'pdd.ddk.resource.url.gen',
                'client_id' => $this->clientId,
                'data_type' => 'JSON',
                'pid' => $this->pid,
                'url' => $url,
                'resource_type' => $resourceType,
                'generate_we_app' => "true",
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $data = ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
            if (!empty($data) && isset($data['resource_url_response'])) {
                return $data['resource_url_response']['we_app_info'];
            }
            Yii::info(['func_name' => 'Pdd.getPromotionActivityUrl', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Pdd.getPromotionActivityUrl', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 商品推荐
     *
     * @param int    $activityTags 4-秒杀，7-百亿补贴，10851-千万补贴，10913-招商礼金商品，31-品牌黑标，10564-精选爆品-官方直推爆款，10584-精选爆品-团长推荐，24-品牌高佣
     * @param int    $page
     * @param int    $pageSize
     * @param string $goodsSign    当channelType=3时必须传
     * @param int $channelType  1-今日销量榜,3-相似商品推荐,4-猜你喜欢(和进宝网站精选一致),5-实时热销榜,6-实时收益榜。默认值5
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 15:49
     */
    public function getRecommendProductList($activityTags = 7, $page = 1, $pageSize = 20, $goodsSign = '', $channelType = 5)
    {
        $now = time();
        try {
            $paramArr = [
                'timestamp' => time(),
                'type' => 'pdd.ddk.goods.recommend.get',
                'client_id' => $this->clientId,
                'data_type' => 'JSON',
                'pid' => $this->pid,
                'limit' => $pageSize,
                'offset' => $pageSize * ($page - 1),
                'channel_type' => $channelType,
                'activity_tags' => json_encode([$activityTags]),
            ];
            if ($channelType == 3) {
                $paramArr['goods_sign_list'] = json_encode([$goodsSign]);
            }
            $paramArr['sign'] = $this->getSignature($paramArr);
            $data = ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
            if (!empty($data) && isset($data['goods_basic_detail_response'])) {
                $productList = [];
                $goodsList = $data['goods_basic_detail_response']['list'];
                if (!empty($goodsList)) {
                    // 获取商品id
                    $goodsSignList = [];
                    foreach ($goodsList as $goods) {
                        $goodsSignList[] = $goods['goods_sign'];
                    }

                    // 调用获取推广地址接口
                    $productUrlList = $this->getPromotionProductUrl($goodsSignList);

                    foreach ($goodsList as $key => $value) {
                        // 标签
                        $tagArr = [];
                        foreach ($value['unified_tags'] as $tag) {
                            if (!empty($tag)) {
                                $tagArr[] = $tag;
                            }
                        }

                        $saleNum = $value['sales_tip'];
                        if (strpos($saleNum, "万") !== false) {
                            $saleNum = intval($saleNum) * 10000;
                        }

                        // 价格
                        if (isset($value['min_group_price'])) {
                            $price = floatval($value['min_group_price'] / 100);
                        } else {
                            $price = floatval($value['min_normal_price'] / 100);
                        }

                        // 佣金
                        $cashBackPrice = round(intval($price) * $value['promotion_rate'] / 1000, 2);

                        // 推广地址
                        if (empty($productUrlList[$key]['we_app_info']['page_path'])) {
                            continue;
                        }
                        $clickUrl = $productUrlList[$key]['we_app_info']['page_path'];
                        $appId = $productUrlList[$key]['we_app_info']['app_id'];

                        // 图片地址
                        $imageUrlArr = [$value['goods_image_url']];

                        $productData = [];
                        $productData['business_product_id'] = $value['goods_id'];
                        $productData['title'] = $value['goods_name'];
                        $productData['app_id'] = $appId;
                        $productData['click_url'] = $clickUrl;
                        $productData['price'] = $price;
                        $productData['cash_back_price'] = $cashBackPrice;
                        $productData['comment_num'] = 0;
                        $productData['sale_num'] = intval($saleNum);
                        $productData['pics'] = json_encode($imageUrlArr);
                        $productData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                        $productData['shop_name'] = $value['mall_name'];
                        $productData['source_id'] = 2;
                        $productData['search_keyword'] = $value['category_name'];
                        $productData['created_at'] = $now;

                        $productList[] = $productData;
                    }
                    // 写入到临时数据表中
                    if (!empty($productList)) {
                        Yii::$app->db->createCommand()->batchInsert(BusinessProductTmp::tableName(), ['business_product_id', 'title', 'app_id', 'click_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'source_id', 'search_keyword', 'created_at'], $productList)->execute();
                    }
                }
                return $productList;
            }
            Yii::info(['func_name' => 'Pdd.getRecommendProductList', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Pdd.getRecommendProductList', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 按订单更新时间查找仅24小时订单列表
     *
     * @param int $endUpdateTime
     * @param int $page
     * @param int $pageSize
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/10/11 16:45
     */
    public function getOrderListByUpdateTime($endUpdateTime, $page = 1, $pageSize = 50)
    {
        $startUpdateTime = $endUpdateTime - 86400;
        try {
            $paramArr = [
                'timestamp' => time(),
                'type' => 'pdd.ddk.order.list.increment.get',
                'client_id' => $this->clientId,
                'data_type' => 'JSON',
                'end_update_time' => $endUpdateTime,
                'start_update_time' => $startUpdateTime,
                'page' => $page,
                'page_size' => $pageSize,
            ];
            $paramArr['sign'] = $this->getSignature($paramArr);
            $data = ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
            if (!empty($data) && isset($data['order_list_get_response'])) {
                return $data['order_list_get_response']['order_list'];
            }
            Yii::info(['func_name' => 'Pdd.getOrderListByUpdateTime', 'paramArr' => $paramArr, 'data' => $data], 'trace');
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'Pdd.getOrderListByUpdateTime', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 获取pid的备案地址，通过返回的地址进行授权备案
     *
     * @return array|string
     *
     * @author     xudt
     * @date-time  2021/10/9 11:31
     */
    public function getPidBindUrl()
    {
        $paramArr = [
            'timestamp' => time(),
            'type' => 'pdd.ddk.rp.prom.url.generate',
            'client_id' => $this->clientId,
            'data_type' => 'JSON',
            'channel_type' => 10,
            'p_id_list' => json_encode([$this->pid]),
        ];
        $paramArr['sign'] = $this->getSignature($paramArr);
        return ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
    }

    /**
     * 查询pid是否备案成功
     *
     * @return array|string
     *
     * @author     xudt
     * @date-time  2021/10/9 11:34
     */
    public function checkPid()
    {
        $paramArr = [
            'timestamp' => time(),
            'type' => 'pdd.ddk.member.authority.query',
            'client_id' => $this->clientId,
            'data_type' => 'JSON',
            'pid' => $this->pid,
        ];
        $paramArr['sign'] = $this->getSignature($paramArr);
        return ToolsHelper::sendRequest($this->domain, "POST", $paramArr);
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
        $string = $this->clientKey . $string . $this->clientKey;
        return strtoupper(md5($string));
    }
}