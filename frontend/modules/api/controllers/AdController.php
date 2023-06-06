<?php
/**
 * 广告接口
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/4/23 15:54
 */

// 格子广告
//"adParams" => [
//    "gridCount" => 8,
//],

// banner广告
//"adParams" => [
//    "adIntervals" => 30,
//],

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use Yii;

class AdController extends BaseController
{
    const BANNER_AD_TYPE = 1; // bannar广告
    const VIDEO_AD_TYPE = 2; // 视频广告
    const TMP_AD_TYPE = 3; // 原生模板广告
    const BOX_AD_TYPE = 4; // 格子广告
    const SCREEN_AD_TYPE = 5; // 插屏广告
    const ENCOURAGE_AD_TYPE = 6; // 激励视频

    /**
     * 首页广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/23 16:01
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
//        if ($uid > 0) { // 会员特权免广告
//            $userLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
//            if ($userLevel >= Yii::$app->params['vipPrivillegeArr'][1]['min_level']) {
//                return ToolsHelper::funcReturn(
//                    "首页广告配置",
//                    true,
//                    [
//                        'screenAdData' => [
//                            "adType" => self::SCREEN_AD_TYPE,
//                            "adUnitId" => "",
//                            "showTotal" => 50,
//                            "intervalTime" => 300000, // 单位为毫秒，5分钟
//                        ],
//                        'flowAdData' => [
//                            "adType" => self::VIDEO_AD_TYPE,
//                            "intervalPageNum" => 8,
//                            "adUnitId" => "",
//                            "adParams" => [],
//                        ],
//                    ]
//                );
//            }
//        }


        $data['screenAdData'] = [
            "adType" => self::SCREEN_AD_TYPE,
//            "adUnitId" => "adunit-4fe2fefff6d1e0cb",
            "adUnitId" => "",
            "showTotal" => 50,
            "intervalTime" => 300000, // 单位为毫秒，5分钟
        ];

        $flowAdData = [
            "adType" => self::VIDEO_AD_TYPE,
            "intervalPageNum" => 8,
            "adUnitId" => "adunit-6b4c55831ca36be4",
            "adParams" => [],
        ];

//        if (isset($userInfo['created_at']) && $userInfo['created_at'] > 0 && $userInfo['created_at'] + 86400 * 3 < time()) {
//            // 按概率随机下发广告配置
//            $randNum = rand(1, 100);
//            if ($randNum > 0 && $randNum <= 80) {
//                $flowAdData = [
//                    "adType" => self::VIDEO_AD_TYPE,
//                    "intervalPageNum" => 8,
//                    "adUnitId" => "adunit-6b4c55831ca36be4",
////                "adUnitId" => "",
//                    "adParams" => [],
//                ];
//            } else {
//                $flowAdData = [
//                    "adType" => self::TMP_AD_TYPE,
//                    "intervalPageNum" => 8,
//                    "adUnitId" => "adunit-5826040fdea57fd1",
////                "adUnitId" => "",
//                    "adParams" => [],
//                ];
//            }
//        }


        $data['flowAdData'] = $flowAdData;

        return ToolsHelper::funcReturn("首页广告配置", true, $data);
    }


    /**
     * 热门、特价广告
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/12 23:55
     */
    public function actionIndexProduct()
    {
        // 按概率随机下发广告配置
        $randNum = rand(1, 100);
        if ($randNum > 0 && $randNum <= 20) {
            $flowAdData = [
                "adType" => self::VIDEO_AD_TYPE,
                "intervalPageNum" => 8,
                "adUnitId" => "adunit-6b4c55831ca36be4",
//                "adUnitId" => "",
                "adParams" => [],
            ];
        } else {
            $flowAdData = [
                "adType" => self::TMP_AD_TYPE,
                "intervalPageNum" => 8,
                "adUnitId" => "adunit-5826040fdea57fd1",
//                "adUnitId" => "",
                "adParams" => [],
            ];
        }

        $data['flowAdData'] = $flowAdData;
        return ToolsHelper::funcReturn("首页广告配置", true, $data);
    }


    /**
     * 宝贝详情广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 08:36
     */
    public function actionProductInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
//        if ($uid > 0) { // 会员特权免广告
//            $userLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
//            if ($userLevel >= Yii::$app->params['vipPrivillegeArr'][1]['min_level']) {
//                return ToolsHelper::funcReturn(
//                    "宝贝详情页广告配置",
//                    true,
//                    [
//                        'topAdData' => [
//                            "adType" => self::VIDEO_AD_TYPE,
//                            "adUnitId" => "",
//                            "adParams" => [],
//                        ],
//                        'bottomAdData' => [
//                            "adType" => self::VIDEO_AD_TYPE,
//                            "adUnitId" => "",
//                            "adParams" => [],
//                        ],
//                    ]
//                );
//            }
//        }


        $randNum = rand(1, 100);
        $topAdData = [];
        if ($randNum > 0 && $randNum <= 20) {
            $topAdData = [
                "adType" => self::VIDEO_AD_TYPE,
//                "adUnitId" => "",
                "adUnitId" => "adunit-af2726b1d581385c",
                "adParams" => [],
            ];
        } elseif ($randNum > 20 && $randNum <= 100) {
            $topAdData = [
                "adType" => self::TMP_AD_TYPE,
//                "adUnitId" => "",
                "adUnitId" => "adunit-b1102e0204b65a3d",
                "adParams" => [],
            ];
        }

        $data['topAdData'] = $topAdData;
        $data['bottomAdData'] = [
            "adType" => self::VIDEO_AD_TYPE,
            "adUnitId" => "",
//            "adUnitId" => "adunit-07581e33dc12fbfd",
            "adParams" => [],
        ];
        $data['screenAdData'] = [
            "adType" => self::SCREEN_AD_TYPE,
            "adUnitId" => "adunit-200f3b19dcff8e52",
            "showTotal" => date("G") >= 12 ? (date("G") >= 17 ? 3 : 2) : 1,
        ];
        return ToolsHelper::funcReturn("宝贝详情页广告配置", true, $data);
    }

    /**
     * 兼职详情广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 08:36
     */
    public function actionJobInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
//        if ($uid > 0) { // 会员特权免广告
//            $userLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
//            if ($userLevel >= Yii::$app->params['vipPrivillegeArr'][1]['min_level']) {
//                return ToolsHelper::funcReturn(
//                    "宝贝详情页广告配置",
//                    true,
//                    [
//                        'topAdData' => [
//                            "adType" => self::VIDEO_AD_TYPE,
//                            "adUnitId" => "",
//                            "adParams" => [],
//                        ],
//                        'bottomAdData' => [
//                            "adType" => self::VIDEO_AD_TYPE,
//                            "adUnitId" => "",
//                            "adParams" => [],
//                        ],
//                    ]
//                );
//            }
//        }


        $randNum = rand(1, 100);
        $topAdData = [];
        if ($randNum > 0 && $randNum <= 80) {
            $topAdData = [
                "adType" => self::VIDEO_AD_TYPE,
//                "adUnitId" => "",
                "adUnitId" => "adunit-af2726b1d581385c",
                "adParams" => [],
            ];
        } elseif ($randNum > 80 && $randNum <= 100) {
            $topAdData = [
                "adType" => self::TMP_AD_TYPE,
//                "adUnitId" => "",
                "adUnitId" => "adunit-b1102e0204b65a3d",
                "adParams" => [],
            ];
        }

        $data['topAdData'] = $topAdData;
        $data['bottomAdData'] = [
            "adType" => self::VIDEO_AD_TYPE,
            "adUnitId" => "",
//            "adUnitId" => "adunit-07581e33dc12fbfd",
            "adParams" => [],
        ];
        $data['screenAdData'] = [
            "adType" => self::SCREEN_AD_TYPE,
//            "adUnitId" => "",
            "adUnitId" => "adunit-200f3b19dcff8e52",
            "showTotal" => date("G") >= 12 ? (date("G") >= 17 ? 3 : 2) : 1,
        ];
        return ToolsHelper::funcReturn("兼职详情页广告配置", true, $data);
    }

    /**
     * 消息列表广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 08:36
     */
    public function actionMessageList()
    {
        $data = [
            'flowAdData' => [
                "adType" => self::TMP_AD_TYPE,
                "intervalPageNum" => 5,
//                "adUnitId" => "adunit-b2502ecc3caf4782",
                "adUnitId" => "",
                "adParams" => [],
            ],
            'bottomAdData' => [
                "adType" => self::VIDEO_AD_TYPE,
                "adUnitId" => "",
                "adParams" => [],
            ],
        ];
        return ToolsHelper::funcReturn("消息列表广告配置", true, $data);
    }

    /**
     * 系统消息广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 09:25
     */
    public function actionMessageSystem()
    {
        $data = [
            'flowAdData' => [
                "adType" => self::TMP_AD_TYPE,
                "intervalPageNum" => 5,
                "adUnitId" => "",
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("系统消息广告配置", true, $data);
    }

    /**
     * 分类页广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 09:32
     */
    public function actionCategory()
    {
        $data = [
            'flowAdData' => [
                "adType" => self::TMP_AD_TYPE,
                "evenIntervalPageNum" => 5,
                "oddIntervalPageNum" => 5,
//                "adUnitId" => "adunit-77bfc2b6c17de4aa",
                "adUnitId" => "",
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("分类页广告配置", true, $data);
    }

    /**
     * 我的广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:16
     */
    public function actionMycenter()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        // 插屏广告
        $data['screenAdData'] = [
            "adType" => self::SCREEN_AD_TYPE,
            "adUnitId" => "",
            "showTotal" => 1,
        ];
//        if (isset($userInfo['created_at']) && $userInfo['created_at'] > 0 && $userInfo['created_at'] + 86400 * 3 < time()) {
//            $data['screenAdData'] = [
//                "adType" => self::SCREEN_AD_TYPE,
//                "adUnitId" => "adunit-1f5b8200ffbb1483",
//                "showTotal" => 1,
//            ];
//        }
        return ToolsHelper::funcReturn("我的广告配置", true, $data);
    }

    /**
     * 做任务赚积分
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:16
     */
    public function actionTask()
    {
        $data = [
            'encourageAdData' => [
                "adType" => self::ENCOURAGE_AD_TYPE,
                "adUnitId" => "adunit-91a5dc5d3a9f6399",
            ],
            'adData' => [
                "adType" => self::BANNER_AD_TYPE,
//                "adUnitId" => "adunit-35f9f20307478cbc",
                "adUnitId" => "",
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("做任务赚积分", true, $data);
    }

    /**
     * 我的主页广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:24
     */
    public function actionMycenterVisit()
    {
        $data = [
            'flowAdData' => [
                "adType" => self::TMP_AD_TYPE,
                "evenIntervalPageNum" => 5,
                "oddIntervalPageNum" => 5,
//                "adUnitId" => "adunit-ec47752b2415c2f5",
                "adUnitId" => "",
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("我的主页广告配置", true, $data);
    }


    /**
     * 我的发布广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:24
     */
    public function actionMycenterProduct()
    {
        $data = [
            'flowAdData' => [
                "adType" => self::BANNER_AD_TYPE,
                "intervalPageNum" => 5,
//                "adUnitId" => "adunit-da435ebb5c5cba04",
                "adUnitId" => "",
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("我的发布广告配置", true, $data);
    }


    /**
     * 我的浏览、点赞等广告配置
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:32
     */
    public function actionProductList()
    {
        $type = Yii::$app->request->get('type', 'view');
        switch ($type) {
            case "view":
                $adUnitId = "adunit-4f06edc53f97390d";
                break;
            case "thumb":
                $adUnitId = "adunit-c37176c932f86f81";
                break;
            case "comment":
                $adUnitId = "adunit-21d267bafd4d280d";
                break;
            case "want":
                $adUnitId = "adunit-3e131afb2dddde45";
                break;
            case "sale":
                $adUnitId = "adunit-241bd00001e5142e";
                break;
            default:
                $adUnitId = "adunit-4f06edc53f97390d";
        }

        $data = [
            'flowAdData' => [
                "adType" => self::TMP_AD_TYPE,
                "evenIntervalPageNum" => 5,
                "oddIntervalPageNum" => 5,
//                "adUnitId" => $adUnitId,
                "adUnitId" => "",
                // 视频广告、原生模板广告
                "adParams" => [],
            ]
        ];
        return ToolsHelper::funcReturn("我的行为广告配置", true, $data);
    }

    /**
     * 转盘抽奖看激励视频
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/24 10:16
     */
    public function actionWheel()
    {
        $data = [
            'encourageAdData' => [
                "adType" => self::ENCOURAGE_AD_TYPE,
                "adUnitId" => "adunit-b2a2655cd391f5bd",
            ]
        ];
        return ToolsHelper::funcReturn("转盘抽奖激励视频", true, $data);
    }

}