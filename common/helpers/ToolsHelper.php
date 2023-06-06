<?php
/**
 * 小工具助手类
 *
 * @author xudt
 * @date   : 2019/11/2 11:19
 */

namespace common\helpers;

use common\services\FanliService;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;
use Yii;

class ToolsHelper
{
    /**
     * 随机验证码
     *
     * @param int $num
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/8/18 19:22
     */
    public static function getSmsCode($num = 6)
    {
        $code = "";
        for ($i = 0; $i < $num; $i++) {
            $code .= rand(0, 9);
        }
        return $code;
    }

    /**
     * 检测手机号是否正确
     *
     * @param string $phone
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/2 11:28
     *
     */
    public static function VerfiyPhone($phone = '')
    {
        $reg = '/^1[345789]\d{9}$/';
        if (preg_match($reg, $phone)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测验证码是否正确
     *
     * @param string $code
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/7 17:36
     *
     */
    public static function VerfiyPhoneCode($code = '')
    {
        $reg = '/\d{6}/';
        if (preg_match($reg, $code)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 密码包含 数字,英文,字符中的两种以上，长度6-20
     *
     * @param string $password
     *
     * @return bool
     * @author   xudt
     * @dateTime 2019/11/11 19:00
     *
     */
    public static function VerfiyPassword($password = '')
    {
        $reg = '/^(?![0-9]+$)(?![a-z]+$)(?![A-Z]+$)(?!([^(0-9a-zA-Z)])+$).{6,20}$/';
        if (preg_match($reg, $password)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 随机生成用户昵称
     *
     *
     * @return string
     * @author   xudt
     * @dateTime 2019/11/14 19:25
     *
     */
    public static function makeNickname()
    {
        $nickname = 'xz_';
        $unique = substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        return $nickname . $unique;
    }

    /**
     * 生成邀请码
     *
     * @param $userId
     *
     * @return string
     * @author   xudt
     * @dateTime 2019/11/14 19:32
     *
     */
    public static function createInviteCode($userId)
    {
        static $sourceString = 'E5FCDG3HQA4B1NPJ2RSTUV67MWX89KLYZ';
        $num = $userId;
        $code = '';
        $len = strlen($sourceString);
        while ($num > 0) {
            $mod = $num % $len;
            $num = ($num - $mod) / $len;
            $code = $sourceString[$mod] . $code;
        }
        if (empty($code[5])) {
            $code = str_pad($code, 6, '0', STR_PAD_LEFT);
        }
        return $code;
    }

    /**
     * 统一输出字符串类型
     *
     * @param $array
     *
     * @return array|string
     * @author   xudt
     * @dateTime 2019/11/25 15:14
     *
     */
    public static function intToString($array)
    {
        if (is_numeric($array)) {
            $array = (string)$array;
        }

        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = self::intToString($value);
            }
        }

        return $array;
    }

    /**
     * 获取一个永不重复的字符串
     *
     * @param string $prefix
     *
     * @return string
     * @author   xudt
     * @dateTime 2020/3/21 15:57
     *
     */
    public static function getUniqidKey($prefix = 'article')
    {
        return md5(uniqid($prefix . rand(0, 10000), true));
    }

    /**
     * 计算时间戳至当前时间倒计时
     *
     * @param int $timestmap
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2020/12/2 11:14
     */
    public static function getCountDownTime($timestmap = 0)
    {
        $now = time();
        $second = $timestmap - $now;
        if ($second < 0) {
            return '0:0:0';
        }
        $hourNum = $minuteNum = $secondNum = 0;
        if ($second >= 3600) {
            $hourNum = floor($second / 3600);
            $second = $second % 3600;
        }

        if ($second >= 60) {
            $minuteNum = floor($second / 60);
            $secondNum = $second % 60;
        }

        return $hourNum . ':' . $minuteNum . ':' . $secondNum;
    }

    /**
     * 计算时间戳和当前时间相差多久转换
     *
     * @param int $timestmap
     *
     * @return false|string
     * @author   xudt
     * @dateTime 2020/3/23 20:54
     *
     */
    public static function getTimeStrDiffNow($timestmap = 0)
    {
        $now = time();
        $second = $now - $timestmap;
        if ($second < 0) {
            return '';
        }
        $dayNum = $hourNum = $minuteNum = 0;
        if ($second >= 86400) {
            $dayNum = floor($second / 86400);
            $second = $second % 86400;
        }
        if ($dayNum >= 4) { //显示日期
            return date("Y-m-d", $timestmap);
        } elseif ($dayNum > 0 && $dayNum < 4) {
            return $dayNum . "天前";
        }

        if ($second >= 3600) {
            $hourNum = floor($second / 3600);
            $second = $second % 3600;
        }

        if ($hourNum > 0) {
            return $hourNum . "小时前";
        }

        if ($second >= 60) {
            $minuteNum = floor($second / 60);
        }

        if ($minuteNum > 0) {
            return $minuteNum . "分钟前";
        }

        return "刚刚";
    }

    /**
     * 发起http请求
     *
     * @param        $url
     * @param        $data
     * @param string $method
     *
     * @return array|string
     * @author   xudt
     * @dateTime 2020/5/12 13:33
     *
     */
    public static function sendRequest($url, $method = "GET", $data = [])
    {
        try {
            $client = new Client();
            $requestObj = $client->createRequest()
                ->setMethod($method)
                ->setFormat(Client::FORMAT_JSON)
                ->setOptions(
                    [
                        CURLOPT_CONNECTTIMEOUT => 30,
                        CURLOPT_TIMEOUT => 120,
                    ]
                )
                ->setUrl($url);
            if ($method == "POST") {
                $requestObj->setData($data);
            }
            $response = $requestObj->send();
            if ($response->isOk == 'true') {
                $responseData = $response->data;
            } else {
                $responseData = [];
                Yii::info(['url' => $url, 'params' => $data, 'method' => $method, 'data' => $responseData, 'error' => '请求失败'], 'sendRequest');
            }
        } catch (\Exception $e) {
            $responseData = [];
            Yii::info(['url' => $url, 'params' => $data, 'method' => $method, 'data' => $responseData, 'error' => $e->getMessage()], 'sendRequest');
        }
        return $responseData;
    }

    /**
     * 使用原生的方式发送post请求
     *
     * @param $url
     * @param $data
     * @param $needJsonEncode
     *
     * @return mixed
     *
     * @author    xudt
     * @dateTime  2020/11/16 18:09
     */
    public static function postRequestOrigin($url, $data, $needJsonEncode = true)
    {
        try {
            if ($needJsonEncode) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_URL, $url); // url
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // json数据
            $res = curl_exec($ch); // 返回值
            curl_close($ch);
            return $res;
        } catch (\Exception $e) {
            Yii::info(['url' => $url, 'params' => $data, 'error' => $e->getMessage()], 'sendRequest');
            return "{}";
        }
    }

    /**
     * 根据出生年月计算出年龄
     *
     * @param int $birthday
     *
     * @return int
     * @author   xudt
     * @dateTime 2020/5/25 11:26
     */
    public static function getAge($birthday = 0)
    {
        $age = 0;
        if (!empty($birthday)) {
            $age = intval(date("Y")) - intval(date("Y", strtotime($birthday)));
            if ($age >= 100) {
                $age = 0;
            }
        }

        return $age;
    }

    /**
     * 省份简称
     *
     * @param $name
     *
     * @return string|string[]
     */
    public static function getProvinceName($name)
    {
        $searchArr = ['省', '市', '特别行政区', '壮族自治区', '自治区', '回族自治区', '维吾尔自治区'];
        foreach ($searchArr as $key => $value) {
            $name = str_replace($value, '', $name);
        }
        return $name;
    }

    /**
     * 删除图片文件
     *
     * @param string $imgUrl
     *
     * @return false
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/17 21:39
     */
    public static function deleteImg($imgUrl = "")
    {
        if (empty($imgUrl)) {
            return false;
        }
        $imgUrl = str_replace(Yii::$app->params['assetDomain'], "", $imgUrl);
        if (strpos($imgUrl, "http") !== false) { //第三方地址
            return false;
        }
        $pos = strpos($imgUrl, "?");
        if ($pos > 0) {
            $imgUrl = substr($imgUrl, 0, $pos);
        }
        $path = Yii::$app->params['assetDir'] . $imgUrl;
        return @unlink($path);
    }

    /**
     * 获取图片完整地址
     *
     * @param string $imgUrl
     * @param string $defaultUrl
     * @param int    $thumbWidth
     * @param int    $thumbHeight
     *
     * @return mixed|string
     *
     * @author     xudt
     * @date-time  2021/3/16 15:06
     */
    public static function getLocalImg($imgUrl = "", $defaultUrl = "", $thumbWidth = 0, $thumbHeight = 0)
    {
        if (empty($imgUrl)) {
            return $defaultUrl;
        }

        if (strpos($imgUrl, "?imageView2/2/") !== false) {
            return $imgUrl;
        }
        $ext = strtolower(substr($imgUrl, strrpos($imgUrl, '.') + 1)); //扩展名
        if (strpos($imgUrl, "http") === false) { //本地存放路径
            $imgUrl = Yii::$app->params['assetDomain'] . $imgUrl;
        }
        if (strpos($imgUrl, Yii::$app->params['assetDomain']) === false) { //第三方地址
            return $imgUrl;
        }

        //目标图片宽度，高度等比缩放
        if ($thumbWidth != 0 && $thumbHeight == 0) {
            $imgUrl .= "?imageView2/2/w/" . $thumbWidth . "/format/" . $ext;
        }
        //目标图片高度，宽度等比缩放
        if ($thumbWidth == 0 && $thumbHeight != 0) {
            $imgUrl .= "?imageView2/2/h/" . $thumbHeight . "/format/" . $ext;
        }
        //等比缩放，比例值为宽缩放比和高缩放比的较小值
        if ($thumbWidth != 0 && $thumbHeight != 0) {
            $imgUrl .= "?imageMogr2/2/w/" . $thumbWidth . "/h/" . $thumbHeight . "/format/" . $ext;
        }
        return $imgUrl;
    }

    /**
     * 获取文章更新数量
     *
     * @param $articleList
     * @param $lastRequestTime
     *
     * @return int
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/9 07:16
     */
    public static function getArticleUpdateNum($articleList, $lastRequestTime)
    {
        $updateCount = 0;
        if (!empty($articleList)) {
            foreach ($articleList as $key => $value) {
                if ($value['audit_at'] > $lastRequestTime) {
                    $updateCount++;
                }
            }
        }
        return $updateCount;
    }

    /**
     * 将一维数组转换为下拉列表数组
     *
     * @param array $array
     * @param array $array2
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2020/11/30 10:39
     */
    public static function convertSelectOptionArr(array $array = [], array $array2 = [])
    {
        if (!empty($array2)) {
            $array = $array2 + $array;
        }
        $data = [];
        if (!empty($array)) {
            foreach ($array as $value => $label) {
                $data[] = [
                    'label' => $label,
                    'value' => $value
                ];
            }
        }
        return $data;
    }

    /**
     * 生成商务订单号
     *
     * @param $uid
     * @param $type
     *
     * @return string
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/30 13:42
     */
    public static function buildTradeNo($uid, $type)
    {
        $uidSerial = $uid - 100000;
        $orderNo = $type . "-" . $uidSerial . date("Ymd");
        if (strlen($orderNo) < 20) {
            $length = 20 - strlen($orderNo);
            $rand = mt_rand(pow(10, $length - 1), pow(10, $length) - 1);
            $orderNo .= $rand;
        }
        return $orderNo;
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    public static function getIp()
    {
        //信任的代理IP，需要各项目按实际情况来填，比如说影视使用了varnish，需将varnish的ip填到信任代理里面，否则取到的是varnish的ip而不是用户的ip
        $allowProxys = array();//信任IP暂时为空
        $ip = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '';
        $ip = trim($ip);
        //如果不是信任的代理ip则直接返回
        if (in_array($ip, $allowProxys) || strpos($ip, '172.16') === 0) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim(array_pop($ips));
            }
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        } else {
            return '';
        }
    }

    /**
     * 数组转xml
     *
     * @param $arr
     *
     * @return string
     * @author xudt<xudengtang@km.com>
     * @date   2018/7/20 15:19
     */
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 随机生成指定数量指定金额的红包
     *
     * @param $sum
     * @param $count
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2020/12/10 20:55
     */
    public function randMoney($sum, $count)
    {
        $arr = [];
        $hes = 0;
        $hess = 0;
        for ($i = 0; $i < $count; $i++) {
            $rand = rand(1, 1000);
            $arr[] = $rand;
            $hes += $rand;
        }
        $arr2 = [];
        foreach ($arr as $key => $value) {
            $round = round(($value / $hes) * $sum, 2);
            $arr2[] = $round;
            $hess += $round;
        }
        if ($sum != round($hess, 2)) {
            $hesss = round($sum - $hess, 2);
            $arr2[0] = $arr2[0] + $hesss;
        }
        return $arr2;
    }

    /**
     * 对字符处理，超出的以省略符替代
     *
     * @param string $string
     * @param int    $length
     *
     * @return mixed|string
     *
     * @author     xudt
     * @date-time  2021/4/13 17:57
     */
    public static function ellipsisStr($string = '', $length = 8)
    {
        if (strlen($string) > $length * 3) {
            return mb_substr($string, 0, $length) . "...";
        }
        return $string;
    }

    /**
     * 根据用户当前积分判断用户等级
     *
     * @param $point
     *
     * @author     xudt
     * @date-time  2021/6/17 11:38
     */
    public static function getUserLevel($point = 0)
    {
        $levelArr = Yii::$app->params['userLevelArr'];
        foreach ($levelArr as $score => $level) {
            if ($point >= $score) {
                return $level;
            }
        }
        return 0;
    }

    /**
     * 获取封面图片高度
     *
     * @param $coverUrl
     * @param $isRemote  是否为远程图片
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/7/2 12:01
     */
    public static function getCoverHeight($coverUrl, $isRemote = 0)
    {
        $coverHeight = 345;
        try {
            if ($isRemote) {
                $coverRealUrl = $coverUrl;
            } else {
                $coverRealUrl = "/var/www/html/asset" . $coverUrl;
            }
            $imageInfo = getimagesize($coverRealUrl);
            $coverHeight = 345;
            if (!empty($imageInfo)) {
                $coverHeight = intval(((690 * $imageInfo[1]) / $imageInfo[0]) / 2);
            }
        } catch (\Exception $e) {
            // 增加错误日志
            Yii::info(['coverUrl' => $coverUrl], "imageNotFound");
        }
        return $coverHeight;
    }

    /**
     * 获取打卡推荐等级
     *
     * @param $viewNum
     * @param $tuijianNum
     * @param $noTuijianNum
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/7/29 14:04
     */
    public static function getTuijianLevel($viewNum, $tuijianNum, $noTuijianNum)
    {
        if ($viewNum >= 300) {
            $level = 9;
            if ($tuijianNum > $noTuijianNum * 2) {
                $level = 10;
            } elseif ($noTuijianNum >= $tuijianNum * 2) {
                $level = 8;
            }
        } elseif ($viewNum >= 100) {
            $level = 8;
            if ($tuijianNum > $noTuijianNum * 2) {
                $level = 9;
            } elseif ($noTuijianNum >= $tuijianNum * 2) {
                $level = 7;
            }
        } else {
            $level = 6;
            if ($tuijianNum > $noTuijianNum * 2) {
                $level = 7;
            }
        }
        return $level;
    }

    /*
     * 导航
     *
     * @param int   $pageType
     * @param array $data
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/7/25 14:18
     */
    public static function getNavListByPageType($pageType = 0, $data = [])
    {
        $navList = Yii::$app->params['navList'];
        foreach ($navList as $key => $value) {
            if ($pageType == 0) { // 首页
                if ($data['version_num'] >= 50000) {
                    if (!in_array($value['style'], ['nav-jifen', 'nav-jd', 'nav-pdd', 'nav-jianzhi', 'nav-hot', 'nav-groupbuy', 'nav-chongwu', 'nav-zufang'])) {
                        unset($navList[$key]);
                    }
                } else {
                    if (!in_array($value['style'], ['nav-jifen', 'nav-jd', 'nav-pdd', 'nav-jianzhi', 'nav-hot', 'nav-low-price', 'nav-chongwu', 'nav-zufang'])) {
                        unset($navList[$key]);
                    }
                }
            } elseif ($pageType == 1) { // 闲置物品详情页
                if ($data['version_num'] >= 50000) {
                    if (!in_array($value['style'], ['nav-jianzhi', 'nav-groupbuy', 'nav-chongwu', 'nav-zufang'])) {
                        unset($navList[$key]);
                    }
                } else {
                    if (!in_array($value['style'], ['nav-jianzhi', 'nav-pdd', 'nav-chongwu', 'nav-zufang'])) {
                        unset($navList[$key]);
                    }
                }
            } elseif ($pageType == 2) { // 打卡详情页
                if (!in_array($value['style'], ['nav-hot', 'nav-low-price', 'nav-chongwu', 'nav-zufang'])) {
                    unset($navList[$key]);
                }
            } elseif ($pageType == 3) { // 我的
                if (!in_array($value['style'], ['nav-jifen', 'nav-jd', 'nav-pdd', 'nav-gongzhonghao'])) {
                    unset($navList[$key]);
                }
            }
        }
        ArrayHelper::multisort($navList, 'sort', SORT_ASC);
        return array_values($navList);
    }

    /**
     * 审核期间不出现悬浮按钮
     *
     * @param $versionNum
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/8/29 18:33
     */
    public static function showButton($versionNum)
    {
//        return 1;
        if ($versionNum == Yii::$app->params['auditVersionNum']) {
            return 0;
        } else {
            return 1;
        }
    }


    /**
     * 获取区域范围数组
     *
     * @param int $isShortTitle
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/9/24 16:54
     */
    public static function getDistTypeList($isShortTitle = 0)
    {
        $distTypeList = Yii::$app->params['distType'];
        $data = [];
        foreach ($distTypeList as $index => $value) {
            $title = $value['title'];
            if ($isShortTitle == 1) {
                $title = $value['short_title'];
            }
            $data[$index] = $title;
        }
        return $data;
    }

    /**
     * 拼多多标签简化
     *
     * @param $tag
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/10/10 15:05
     */
    public static function pddTagShort($tag)
    {
        if (strpos($tag, "券") !== false) {
            return "券";
        }
        if (strpos($tag, "销") !== false || strpos($tag, "评") !== false || strpos($tag, "榜") !== false) {
            return "热销";
        }
        if (strpos($tag, "险") !== false) {
            return "运费险";
        }
        if (strpos($tag, "旗舰店") !== false) {
            return "旗舰店";
        }
        if (strpos($tag, "降") !== false) {
            return "降价";
        }
        return "";
    }

    /**
     * 返利价格
     *
     * @param $cashBackPrice
     *
     * @return float
     *
     * @author     xudt
     * @date-time  2021/10/11 22:19
     */
    public static function getCashBackPrice($cashBackPrice)
    {
        if ($cashBackPrice > 10) {
            return round($cashBackPrice * 0.5, 2);
        } elseif ($cashBackPrice > 1) {
            return round($cashBackPrice * 0.7, 2);
        } else {
            return $cashBackPrice;
        }
    }

    /**
     * 随机产生一个频道
     *
     * @param int $sourceId
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/10/29 11:40
     */
    public static function getBusinessProductChannelType($sourceId = 0)
    {
        if (empty($sourceId)) {
            $sourceId = rand(1, 2);
        }
        $chosenChannelTypeArr = Yii::$app->params['chosenChannelTypeArr'][$sourceId];
        $chosenIndex = array_rand($chosenChannelTypeArr, 1);
        return $chosenChannelTypeArr[$chosenIndex];
    }

    /**
     * 订单状态
     *
     * @param array $orderInfo
     *
     * @return mixed|string
     *
     * @author     xudt
     * @date-time  2021/10/18 10:34
     */
    public static function getOrderStatus($orderInfo = [])
    {
        if ($orderInfo['return_success'] == 1) {
            return "已返利";
        }
        $businessProductOrderStatus = Yii::$app->params['businessProductOrderStatus'];
        if (isset($businessProductOrderStatus[$orderInfo['source_id']][$orderInfo['status']])) {
            return $businessProductOrderStatus[$orderInfo['source_id']][$orderInfo['status']];
        } else {
            return "无效";
        }
    }

    /**
     * 订单数奖励列表
     *
     * @param int $uid
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/20 10:40
     */
    public static function getOrderNumAward($uid)
    {
        $orderCount = 0;
        if (!empty($uid)) {
            $fanliService = new FanliService();
            $statisticData = $fanliService->getStatisticData($uid);
            $orderCount = $statisticData['order_count'];
        }
        $data = [];
        $orderNumAwardArr = Yii::$app->params['orderNumAwardArr'];
        foreach ($orderNumAwardArr as $orderNum => $point) {
            $isOver = $orderCount >= $orderNum ? 1 : 0;
            $data[] = [
                'order_num' => $orderNum,
                'point' => $point > 10000 ? intval($point / 10000) . "w" : $point,
                'is_over' => $isOver,
            ];
        }
        return $data;
    }

    /**
     * 为订单生成二维码的签名
     *
     * @param $uid
     * @param $orderId
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/11/24 15:44
     */
    public static function getOrderSign($uid, $orderId)
    {
        $paramSignSecret = Yii::$app->params['paramSignSecret'];
        return md5($uid . "-" . $orderId . "-" . $paramSignSecret);
    }

    /**
     * 商家核验订单的密钥
     *
     * @param $orderId
     * @param $timestamp
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/11/24 15:51
     */
    public static function getOrderCheckSecretKey($orderId, $timestamp)
    {
        $groupBuyOrderSecret = Yii::$app->params['groupBuyOrderSecret'];
        return md5($orderId . $groupBuyOrderSecret . $timestamp);
    }


    /**
     * 方法返回数据格式
     *
     * @param $result
     * @param $message
     * @param $data
     *
     * @return array
     * @author   xudt
     * @dateTime 2019/11/2 11:40
     *
     */
    public static function funcReturn($message = '', $result = false, $data = [])
    {
        return [
            'result' => $result,
            'message' => $message,
            'data' => $data,
        ];
    }
}