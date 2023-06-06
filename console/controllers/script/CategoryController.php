<?php
/**
 * 解析京东的分类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/9/9 16:17
 */

namespace console\controllers\script;

use common\models\ProductCategory;
use yii\console\Controller;
use yii\console\ExitCode;

class CategoryController extends Controller
{
    /**
     * 京东
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/9 17:21
     */
    public function actionJd()
    {
        $pid = 26;
        $filePath = __DIR__ . "/category/" . $pid . ".html";
        $content = file_get_contents($filePath);

        $pregRex = '/<div class="jd-category-div cur">(.*)<div style=\"clear:both\"><\/div><\/ul><\/div>/U';
        preg_match_all($pregRex, $content, $matchContentArr);
        foreach ($matchContentArr[0] as $contentDiv) {
            // 读取二级标题
            $pregRex = '/<div class=\"jd-category-div cur\"><h4>(.*)<\/h4><ul class=\"jd-category-style-1\">/U';
            preg_match_all($pregRex, $contentDiv, $matchArr);
            $categoryArr = $matchArr[1]; // 二级菜单


            // 读取图片及三级标题
            $pregRex = '/<img src=\"(.*)\" id=\".*\"><span>(.*)<\/span>/U';
            preg_match_all($pregRex, $contentDiv, $matchImgArr);
            $imgArr = $matchImgArr[1];
            $titleArr = $matchImgArr[2];


            $productCategory = new ProductCategory();
            $productCategory->category_name = $categoryArr[0];
            $productCategory->pid = $pid;
            $productCategory->category_level = 2;
            if ($productCategory->save()) {
                $categoryId = $productCategory->id;
                foreach ($titleArr as $key => $categoryName) {
                    $productCategoryModel = new ProductCategory();
                    $productCategoryModel->category_name = $categoryName;
                    $productCategoryModel->pid = $categoryId;
                    $productCategoryModel->category_level = 3;
                    if ($productCategoryModel->save()) {
                        // 保存图片到本地
                        $saveDir = __DIR__ . "/category/images/" . $pid . "/" . $categoryId;
                        if (!is_dir($saveDir)) {
                            mkdir($saveDir, 0777, true);
                        }
                        $url = "http:" . $imgArr[$key];
                        try {
                            $imageContent = file_get_contents($url);
                            $saveFilePath = $saveDir . "/" . $productCategoryModel->id . ".jpeg";
                            file_put_contents($saveFilePath, $imageContent);
                        } catch (\Exception $e) {
                            echo $productCategoryModel->id . "-" . $productCategoryModel->category_name . "\r\n";
                        }
                    }
                    sleep(1);
                }
            }
        }
        return ExitCode::OK;
    }

    /**
     * 苏宁
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/9/11 09:49
     */
    public function actionSuning()
    {
        $pid = 1983;
        $filePath = __DIR__ . "/category/" . $pid . ".html";
        $content = file_get_contents($filePath);
        $content = preg_replace("/[\t\n\r]+/", "", $content);

        $pregRex = '/<div class=\"second-module(.*)<\/a>\s*<\/li>\s*<\/ul>\s*<\/div>/U';
        preg_match_all($pregRex, $content, $matchContentArr);
        foreach ($matchContentArr[0] as $contentDiv) {
            // 读取二级标题
            $pregRex = '/<span class=\"second-name fl\">(.*)<\/span>/U';
            preg_match_all($pregRex, $contentDiv, $matchArr);
            $categoryArr = $matchArr[1]; // 二级菜单


            // 读取图片及三级标题
            $pregRex = '/<img src1=\"(.*)\".*>\s*<\/div>\s*<div class=\"third-name\">(.*)<\/div>/U';
            preg_match_all($pregRex, $contentDiv, $matchImgArr);
            $imgArr = $matchImgArr[1];
            $titleArr = $matchImgArr[2];

            $productCategory = new ProductCategory();
            $productCategory->category_name = $categoryArr[0];
            $productCategory->pid = $pid;
            $productCategory->category_level = 2;
            if ($productCategory->save()) {
                $categoryId = $productCategory->id;
                foreach ($titleArr as $key => $categoryName) {
                    $productCategoryModel = new ProductCategory();
                    $productCategoryModel->category_name = $categoryName;
                    $productCategoryModel->pid = $categoryId;
                    $productCategoryModel->category_level = 3;
                    if ($productCategoryModel->save()) {
                        // 保存图片到本地
                        $saveDir = __DIR__ . "/category/" . $pid . "/" . $categoryId;
                        if (!is_dir($saveDir)) {
                            mkdir($saveDir, 0777, true);
                        }
                        $url = $imgArr[$key];
                        try {
                            $imageContent = file_get_contents($url);
                            $saveFilePath = $saveDir . "/" . $productCategoryModel->id . ".jpg";
                            file_put_contents($saveFilePath, $imageContent);
                        } catch (\Exception $e) {
                            echo $productCategoryModel->id . "-" . $productCategoryModel->category_name . "\r\n";
                        }
                    }
                    sleep(1);
                }
            }
        }
        return ExitCode::OK;
    }
}