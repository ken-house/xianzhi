<?php
/**
 * 微信号入库
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/5/28 14:44
 */

namespace console\controllers\script;

use common\models\Wechat;
use common\models\WechatOwner;
use yii\console\Controller;
use yii\console\ExitCode;

use Yii;

class WechatController extends Controller
{
    private $data = [
        'province' => '上海',
        'city' => '上海',
        'area' => '浦东新区',
        'town' => '鹤沙航城',
    ];

    /**
     * 导入数据
     *
     * @param int $startLineNum
     * @param int $type
     * @param int $owner
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/7/3 23:08
     */
    public function actionImport($startLineNum = 0, $type = 0, $owner = 0)
    {
        $filePath = __DIR__ . "/wechat/" . $this->data['town'] . ".txt";
        if ($owner) {
            $filePath = __DIR__ . "/wechat/大本营/" . $this->data['town'] . ".txt";
        }
        $dataArr = $this->readFile($filePath, $startLineNum);
        if (!empty($dataArr)) {
            foreach ($dataArr as $wx) {
                $this->saveData($wx, $type, $owner);
            }
        }
        return ExitCode::OK;
    }

    /**
     * 导出
     *
     * @param     $town
     * @param int $num
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/7/3 23:57
     */
    public function actionExport($town, $num = 20)
    {
        // 目标群
        $targetArr = Wechat::find()->select(['wx'])->where(['town' => $town, 'type' => 0, 'enabled' => 1])->column();

        // 大本营
        $ownerDataArr = WechatOwner::find()->select(['wx'])->where(['town' => $town])->column();

        // 获取未添加到大本营的有效用户
        $dataArr = array_diff($targetArr, $ownerDataArr);
        echo "剩余用户数：" . count($dataArr) . "\r\n";

        // 随机返回个数
        $randArr = array_rand($dataArr, $num);
        $wxArr = [];
        foreach ($randArr as $num) {
            $wx = $dataArr[$num];
            echo $wx . "\r\n";
            $wxArr[] = $wx;
        }
        // 设置正在处理
        Wechat::updateAll(['enabled' => 2], ['wx' => $wxArr]);
        return ExitCode::OK;
    }


    /**
     * 读取文件数据
     *
     * @param $filePath
     * @param $startLineNum
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/7/3 22:08
     */
    private function readFile($filePath, $startLineNum)
    {
        $data = [];
        $lineNum = 1;
        if (file_exists($filePath)) {
            $fp = fopen($filePath, "r");
            while (!feof($fp)) {
                $wx = fgets($fp);
                $wx = str_replace("\n", "", $wx);
                $wx = str_replace("\r", "", $wx);

                // 写入数据库
                if ($lineNum >= $startLineNum) {
                    if (!empty($wx)) {
                        $data[] = $wx;
                    }
                }
                $lineNum++;
            }
            fclose($fp);
        }
        return $data;
    }

    /**
     * 保存到数据库
     *
     * @param $wx
     * @param $type
     * @param $owner
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/7/3 22:19
     */
    private function saveData($wx, $type, $owner)
    {
        $now = time();
        if ($owner == 1) { // 大本营数据直接写入
            $wechatOwnerModel = new WechatOwner();
            $data = $this->data;
            $data['wx'] = $wx;
            $data['created_at'] = $now;
            if (strpos($wx, "wxid_") !== false) {
                $data['enabled'] = 0;
            }
            $wechatOwnerModel->attributes = $data;
            return $wechatOwnerModel->save();
        } else { // 目标群的用户,微信号保持唯一性
            $wechatModel = Wechat::find()->where(['wx' => $wx])->one();
            if (empty($wechatModel)) {
                $wechatModel = new Wechat();
                if ($type != 999) {
                    $data = $this->data;
                }
                if (strpos($wx, "wxid_") !== false) {
                    $data['enabled'] = 0;
                }
                $data['type'] = $type;
                $data['wx'] = $wx;
                $data['created_at'] = $now;
                $wechatModel->attributes = $data;
                return $wechatModel->save();
            }
        }
        return false;
    }
}