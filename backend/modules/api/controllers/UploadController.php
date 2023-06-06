<?php
/**
 * @author xudt
 * @date   : 2020/6/7 9:34
 */

namespace backend\modules\api\controllers;

use common\helpers\ToolsHelper;
use Yii;

class UploadController extends BaseController
{
    public $enableCsrfValidation = false;

    /**
     * 上传文章图片
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/6/7 9:40
     */
    public function actionUpload()
    {
        $pageType = Yii::$app->request->post("page_type", "");

        $file = $_FILES['file'];
        $name = $file['name'];
        $type = strtolower(substr($name, strrpos($name, '.') + 1));
        $allowType = array('jpg', 'jpeg', 'gif', 'png');

        if (!in_array($type, $allowType)) {
            return ToolsHelper::funcReturn("不允许的文件格式");
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ToolsHelper::funcReturn("非法上传");
        }

        switch ($pageType) {
            case "group_buy_product":
                $dirName = "/group_buy_product";
                break;
            case "group_buy_shop":
                $dirName = "/group_buy_shop";
                break;
            default:
                $dirName = "/group_buy";
        }
        $fileName = date("Ymd") . "_" . uniqid() . "." . $type;

        if (!is_dir(Yii::$app->params['assetDir'] . $dirName)) {
            mkdir(Yii::$app->params['assetDir'] . $dirName, 755, true);
        }
        $filePath = $dirName . "/" . $fileName;

        if (move_uploaded_file($file['tmp_name'], Yii::$app->params['assetDir'] . $filePath)) {
            return ToolsHelper::funcReturn(
                "上传成功",
                true,
                [
                    'url' => Yii::$app->params['assetDomain'] . $filePath,
                ]
            );
        }
        return ToolsHelper::funcReturn("上传失败");
    }
}