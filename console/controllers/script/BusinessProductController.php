<?php
/**
 * 电商商品
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/9/16 13:58
 */

namespace console\controllers\script;

use common\models\AdvDataEcpmLevel;
use common\models\BusinessProduct;
use yii\console\Controller;
use Yii;
use yii\console\ExitCode;

class BusinessProductController extends Controller
{
    /**
     * 采集京东商品列表
     *
     * @param $categoryStr
     *
     * @return int
     * @throws \yii\db\Exception
     *
     * @author     xudt
     * @date-time  2021/9/22 16:52
     */
    public function actionJd($categoryStr)
    {
        try {
            $categoryArr = explode('-', $categoryStr);
            $category = $categoryArr[0];
            $now = time();
            $filePath = __DIR__ . "/business_product/jd/" . $categoryStr . ".html";
            $content = file_get_contents($filePath);

            $pregRex = '/<div class="card"(.*)<\/button><\/div><\/div>/U';
            preg_match_all($pregRex, $content, $matchContentArr);
            $insertList = [];
            foreach ($matchContentArr[0] as $contentDiv) {
                // 读取图片
                $pregRex = '/<a href="(.*)".*class="imgbox".*><img src="(.*)".*class="goods-img">.*<\/a>.*<p class=\"one\">.*佣金：￥<b>(.*)<\/b><\/span><\/p><p class=\"two\"><a.*>(.*)<\/a><\/p><p class="three clearfix"><span.*>￥(.*)<\/span>.*<\/p><p class="four"><span class="auxiliary-subtext">好评：(.*)<\/span><\/p>.*<div title="(.*)".*class="shop\-detail.*">/U';
                if (preg_match_all($pregRex, $contentDiv, $matchArr)) {
                    if (preg_match('/\/\/item.jd.com\/(.*)\.html/', $matchArr[1][0], $matchIdArr)) {
                        $productId = $matchIdArr[1];
                        $businessProductModel = BusinessProduct::find()->where(['business_product_id' => $productId, 'source_id' => 1])->one();
                        if (!empty($businessProductModel)) { // 已存在，跳过
                            continue;
                        }
                    } else {
                        continue;
                    }

                    $shopName = trim($matchArr[7][0]);
                    $tagArr = [];
                    if (strpos($shopName, "京东自营") !== false) {
                        $tagArr[] = "自营";
                    }
                    $insertData = [];
                    $insertData['business_product_id'] = $productId;
                    $imageUrl = trim($matchArr[2][0]); // 封面图片地址
                    $imageUrlArr = [];
                    $imageUrlArr[] = $imageUrl;
                    $insertData['title'] = trim($matchArr[4][0]);
                    $insertData['url'] = '';
                    $insertData['discount_url'] = '';
                    $insertData['price'] = floatval($matchArr[5][0]);
                    $insertData['cache_back_price'] = floatval($matchArr[3][0]);
                    $insertData['comment_num'] = $matchArr[6][0];
                    $insertData['sale_num'] = 0;
                    $insertData['pics'] = json_encode($imageUrlArr);
                    $insertData['tags'] = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                    $insertData['shop_name'] = $shopName;
                    $insertData['category'] = $category;
                    $insertData['source_id'] = 1;
                    $insertData['status'] = 0;
                    $insertData['updated_at'] = $now;
                    $insertData['created_at'] = $now;

                    $insertList[] = $insertData;
                } else {
                    var_dump($contentDiv);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            if (!empty($insertList)) {
                $row = Yii::$app->db->createCommand()->batchInsert(BusinessProduct::tableName(), ['business_product_id', 'title', 'url', 'discount_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'category', 'source_id', 'status', 'updated_at', 'created_at'], $insertList)->execute();
                if ($row) {
                    sleep(2);
                    // 更新推广地址
                    $filePath = __DIR__ . "/business_product/jd/" . $categoryStr . ".csv";
                    $dataList = $this->getFileData($filePath);
                    if (!empty($dataList)) {
                        foreach ($dataList as $value) {
                            preg_match('/http:\/\/item.jd.com\/(.*)\.html/', $value[1], $matchArr);
                            $businessProductId = $matchArr[1];
                            $businessProductModel = BusinessProduct::find()->where(['business_product_id' => $businessProductId, 'source_id' => 1])->one();
                            if (!empty($businessProductModel)) {
                                $tagArr = json_decode($businessProductModel->tags, true);
                                $discountUrl = trim($value[7]);
                                if (!empty($discountUrl)) {
                                    $tagArr[] = "券";
                                }
                                $businessProductModel->tags = json_encode($tagArr, JSON_UNESCAPED_UNICODE);
                                $businessProductModel->url = trim($value[6]);
                                $businessProductModel->discount_url = $discountUrl;
                                $businessProductModel->updated_at = time();
                                $businessProductModel->status = 1;
                                $businessProductModel->save();
                            }
                        }
                    }
                }
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }


    /**
     * 拼多多推广商品
     *
     * @param $categoryStr
     *
     * @return int
     * @throws \yii\db\Exception
     *
     * @author     xudt
     * @date-time  2021/9/17 14:10
     */
    public function actionPdd($categoryStr)
    {
        $categoryArr = explode('-', $categoryStr);
        $category = $categoryArr[0];
        $now = time();
        $filePath = __DIR__ . "/business_product/pdd/" . $categoryStr . ".html";
        $content = file_get_contents($filePath);

        $pregRex = '/<a class=\"\" href=\".*\" target="_blank">(.*)<\/div><\/div><\/div><\/a>/U';
        preg_match_all($pregRex, $content, $matchContentArr);
        $insertList = [];
        foreach ($matchContentArr[0] as $contentDiv) {
            // 读取图片
            $pregRex = '/<div class="jinbao-single-goods-card jinbao-goods-need-select.* data-trackkey="(.*)".*><img class="animation-img " src="(.*)" style=\".*\">.*<p class=\"goods-title\"><span class=\"text\">(.*)<\/span><\/p>.*<span class=\"data-price left-align\"><span class=\"unit\">￥<\/span><span class=\"unit-left\">(.*)<\/span><\/span>.*<span class=\"data-num left-align\"><span class=\"unit\">￥<\/span><span class=\"unit-left\">(.*)<\/span><\/span>.*<div class=\"goods-sale\">销量(.*)<\/div>.*<div class="store\-name">(.*)<\/div>/U';
            if (preg_match_all($pregRex, $contentDiv, $matchArr)) {
                $insertData = [];
                $tagArr = [];
                $insertData['business_product_id'] = $matchArr[1][0];
                $imageUrl = $matchArr[2]; // 封面图片地址
                $insertData['title'] = $matchArr[3][0];
                $insertData['url'] = '';
                $insertData['discount_url'] = '';
                $insertData['price'] = floatval($matchArr[4][0]);
                $insertData['cache_back_price'] = floatval($matchArr[5][0]);
                $saleNum = $matchArr[6][0];
                if (strpos($saleNum, "万") !== false) {
                    $saleNum = intval($saleNum) * 10000;
                }
                $insertData['comment_num'] = 0;
                $insertData['sale_num'] = intval($saleNum);
                $insertData['pics'] = json_encode($imageUrl);
                $insertData['tags'] = json_encode($tagArr, 310);
                $insertData['shop_name'] = $matchArr[7][0];
                $insertData['category'] = $category;
                $insertData['source_id'] = 2;
                $insertData['status'] = 0;
                $insertData['updated_at'] = $now;
                $insertData['created_at'] = $now;

                $insertList[] = $insertData;
            }
        }

        if (!empty($insertList)) {
            $row = Yii::$app->db->createCommand()->batchInsert(BusinessProduct::tableName(), ['business_product_id', 'title', 'url', 'discount_url', 'price', 'cash_back_price', 'comment_num', 'sale_num', 'pics', 'tags', 'shop_name', 'category', 'source_id', 'status', 'updated_at', 'created_at'], $insertList)->execute();
            if ($row) {
                return ExitCode::OK;
            }
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }


    /**
     * 读取csv文件
     *
     * @param $file
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/16 16:01
     */
    public function getFileData($file)
    {
        if (!is_file($file)) {
            exit('没有文件');
        }

        $handle = fopen($file, 'r');
        if (!$handle) {
            exit('读取文件失败');
        }

        $dataList = [];
        while (($data = fgetcsv($handle)) !== false) {
            $data = eval('return ' . iconv('gbk', 'utf-8', var_export($data, true)) . ';');
            // 跳过第一行标题
            if ($data[0] == '商品名称') {
                continue;
            }
            // data 为每行的数据，这里转换为一维数组
            $dataList[] = $data;
        }
        fclose($handle);
        return $dataList;
    }

    /**
     * 拼多多推广地址
     *
     * @param $id
     * @param $categoryStr
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/22 09:31
     */
    public function actionCopyUrl($categoryStr, $id)
    {
        try {
            $filePath = __DIR__ . "/business_product/jd/" . $categoryStr . ".txt";
            $content = file_get_contents($filePath);
            $matchArr = explode("\n", $content);
            foreach ($matchArr as $url) {
                BusinessProduct::updateAll(['url' => $url, 'status' => 1], ['id' => $id]);
                $id++;
            }
            return ExitCode::OK;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}