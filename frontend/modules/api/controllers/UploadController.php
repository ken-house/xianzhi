<?php
/**
 * 上传文件
 *
 * @author xudt
 * @date   : 2020/6/7 9:34
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\WechatService;
use Yii;

class UploadController extends BaseController
{
    /**
     * 上传文章图片
     *
     * @return array
     * @author   xudt
     * @dateTime 2020/6/7 9:40
     */
    public function actionUploadImg()
    {
        try {
            $userInfo = Yii::$app->params['userRedis'];
            $uid = intval($userInfo['uid']);

            // 页面上传类型
            $pageType = Yii::$app->request->post("page_type", "product");

            $file = $_FILES['file'];
            $name = $file['name'];
            $extType = strtolower(substr($name, strrpos($name, '.') + 1));
            $allowType = array('jpg', 'jpeg', 'gif', 'png');

            if (!in_array($extType, $allowType)) {
                return ToolsHelper::funcReturn("不允许的文件格式");
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                return ToolsHelper::funcReturn("非法上传");
            }

            //内容安全检测
            if ($file['size'] < 1024 * 1024) {
                $wechatService = new WechatService();
                $checkResult = $wechatService->imgSecCheck($file['tmp_name']);
                if (!$checkResult) {
                    return ToolsHelper::funcReturn("图片涉嫌违规");
                }
            }

            $fileName = $uid . "_" . date("Ymd") . "_" . uniqid() . "." . $extType;
            switch ($pageType){
                case "clock":
                    $dirName = "/clock";
                    break;
                case "job":
                    $dirName = "/job";
                    break;
                default:
                    $dirName = "/product";
            }
            if (!is_dir(Yii::$app->params['assetDir'] . $dirName)) {
                mkdir(Yii::$app->params['assetDir'] . $dirName, 755, true);
            }
            $filePath = $dirName . "/" . $fileName;
            if (move_uploaded_file($file['tmp_name'], Yii::$app->params['assetDir'] . $filePath)) {
                return ToolsHelper::funcReturn(
                    "上传成功",
                    true,
                    [
                        'url' => $filePath,
                        'showUrl' => ToolsHelper::getLocalImg($filePath, '', 540), //页面展示地址
                    ]
                );
            }
            return ToolsHelper::funcReturn("上传失败");
        } catch (\Exception $e) {
            return ToolsHelper::funcReturn($e->getMessage());
        }
    }

    /**
     * 删除图片
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/25 17:25
     */
    public function actionDeleteImg()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = intval($userInfo['uid']);
        if ($uid == 0) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $imgUrl = Yii::$app->request->post("img_url", "");
        if (ToolsHelper::deleteImg($imgUrl)) {
            return ToolsHelper::funcReturn("删除成功", true);
        }
        return ToolsHelper::funcReturn("删除失败");
    }
}