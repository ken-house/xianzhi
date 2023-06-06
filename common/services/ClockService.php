<?php
/**
 * 网红打卡地服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/2/19 10:15
 */

namespace common\services;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\Clock;
use common\models\elasticsearch\EsClock;
use common\models\User;
use common\models\UserData;
use Yii;

class ClockService
{
    const STATUS_AUDIT = 0; // 待审核
    const STAUTS_PASS = 1; // 通过
    const STATUS_REFUSE = 2; // 不通过
    const STATUS_DEL = 3; // 删除

    const UID_OFFSET = 100000; // 为了bitmap从0开始；

    /**
     * 从数据库中读取商品信息
     *
     * @param int $uid
     * @param int $clockId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 10:38
     */
    public function getClockInfoFromDb($uid, $clockId)
    {
        $clockInfo = Clock::find()->where(['id' => $clockId])->asArray()->one();
        if (empty($clockInfo)) {
            return ToolsHelper::funcReturn("打卡不存在");
        }

        if ($clockInfo['uid'] != $uid) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $clockInfo['pics'] = !empty($clockInfo['pics']) ? json_decode($clockInfo['pics'], true) : [];
        if (!empty($clockInfo['pics'])) {
            foreach ($clockInfo['pics'] as $k => $picUrl) {
                $clockInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl);
            }
        }

        // 是否为热门
        $clockInfo['is_hot'] = $clockInfo['view_num'] >= 200 ? 1 : 0;
        $clockInfo['info'] = preg_replace("/<p\/>/", "\n", $clockInfo['info']);

        return ToolsHelper::funcReturn("打卡信息", true, ['clockInfo' => $clockInfo]);
    }

    /**
     * 保存打卡信息
     *
     * @param $userInfo
     * @param $clockInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/19 13:40
     */
    public function saveClockInfoToDb($userInfo, $clockInfo)
    {
        $now = time();
        $clockInfo['info'] = preg_replace("/[\n\r]/", "<p/>", $clockInfo['info']);
        $clockInfo['pics'] = str_replace(Yii::$app->params['assetDomain'], "", $clockInfo['pics']);

        if ($clockInfo['id'] == 0) {
            $clockModel = new Clock();
            $clockInfo['uid'] = $userInfo['uid'];
            $clockInfo['created_at'] = $now;
        } else {
            $clockModel = Clock::find()->where(['id' => $clockInfo['id']])->one();
            if ($clockModel->uid != $userInfo['uid']) {
                return ToolsHelper::funcReturn("非法操作");
            }
        }
        $clockInfo['status'] = self::STATUS_AUDIT;
        $clockInfo['updated_at'] = $now;
        $clockModel->attributes = $clockInfo;
        if ($clockModel->save()) {
            // 判断ES的状态是否为审核通过,若为审核通过则不需要审核；
            $clockEsData = EsClock::get($clockModel->id);
            $userLevel = ToolsHelper::getUserLevel($userInfo['reward_point']);
            if ($userLevel >= Yii::$app->params['vipPrivillegeArr'][2]['min_level'] && !empty($clockEsData) && $clockEsData['status'] == ClockService::STAUTS_PASS) {
                $clockInfo['uid'] = $clockModel->uid;
                return $this->autoPass($clockInfo);
            }
            if (empty($clockEsData)) { // 新增
                $rewardPoint = Yii::$app->params['awardType'][13]['point'];
                return ToolsHelper::funcReturn("发布成功，审核通过后将获得" . $rewardPoint . "积分", true);
            }
            return ToolsHelper::funcReturn("发布成功，两小时内完成审核", true);
        }
        return ToolsHelper::funcReturn("发布失败");
    }

    /**
     * 自动审核通过
     *
     * @param array $clockInfo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/6/15 21:37
     */
    private function autoPass($clockInfo = [])
    {
        $now = time();
        $clockModel = Clock::find()->where(['id' => $clockInfo['id']])->one();
        if (empty($clockModel)) {
            return ToolsHelper::funcReturn("不存在该id");
        }

        // 修改数据库
        $clockModel->status = self::STAUTS_PASS;
        $clockModel->audit_at = $now;
        $clockModel->updated_at = $now;
        if ($clockModel->save()) {
            //写入geoRedis
            $this->addClockGeoData($clockInfo['id'], $clockInfo['lat'], $clockInfo['lng']);

            // 更新到ES
            unset($clockInfo['updated_at']); // 修改不更新ES里的更新时间
            $clockInfo['status'] = self::STAUTS_PASS;
            $clockInfo['audit_at'] = $now; // 为了避免不修改进行保存不报错
            $clockInfo['price'] = floatval($clockInfo['price']);
            if (EsClock::update($clockInfo['id'], $clockInfo)) {
                // 增加自动审核日志
                Yii::info(['clockInfo' => $clockInfo], "autoPassClock");
                return ToolsHelper::funcReturn("会员特权，修改无需审核", true);
            }
        }
        return ToolsHelper::funcReturn("发布失败，请联系客服");
    }

    /**
     * 添加商品的位置信息到geoRedis中
     *
     * @param $clockId
     * @param $lat
     * @param $lng
     *
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:37
     */
    public function addClockGeoData($clockId, $lat, $lng)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoClock');

        $redisClient->geoadd($redisKey, $lng, $lat, $clockId);
    }

    /**
     * 从geoRedis中读取附近的打卡列表
     *
     * @param $lat
     * @param $lng
     * @param $distType
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/1 16:44
     */
    private function getClockListFromGeoRedis($lat, $lng, $distType)
    {
        /** @var \Redis $redisClient */
        $redisClient = Yii::$app->get('redisGeo');
        $redisKey = RedisHelper::RK('distGeoClock');
        return $redisClient->georadius($redisKey, $lng, $lat, $distType, 'km', 'WITHDIST');
    }

    /**
     * 我的打卡总数
     *
     * @param     $uid
     * @param int $status
     *
     * @author     xudt
     * @date-time  2021/2/26 20:18
     */
    public function getClockNum($uid, $status = -1)
    {
        if ($status == -1) {
            return Clock::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL])->count();
        } else {
            return Clock::find()->where(['uid' => $uid, 'status' => $status])->count();
        }
    }


    /**
     * 获取用户我发布的
     *
     * @param     $uid
     * @param     $page
     * @param     $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/3/11 11:12
     */
    public function getClockList($uid, $page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $clockModel = Clock::find()->where(['uid' => $uid])->andWhere(['<>', 'status', self::STATUS_DEL]);
        $clockList = $clockModel->offset($start)->limit($pageSize)->orderBy('status asc,updated_at desc')->asArray()->all();
        if (!empty($clockList)) {
            foreach ($clockList as $key => &$value) {
                $value['audit_date'] = !empty($value['audit_at']) ? date("Y-m-d H:i:s", $value['audit_at']) : '';
                $picArr = json_decode($value['pics'], true);
                $value['pics'] = $picArr;
                $value['cover'] = isset($picArr[0]) ? ToolsHelper::getLocalImg($picArr[0]) : '';
                // 打卡数据
                $clockData = $this->getClockData($value['id']);
                isset($clockData['view_num']) && $value['view_num'] = $clockData['view_num'];
            }
        }
        return $clockList;
    }


    /**
     * 从Es格式化输出商品列表
     *
     * @param array $list
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/13 18:37
     */
    private function formatDataListFromEs($list = [])
    {
        $clockList = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $item = $value['_source'];
                $picArr = json_decode($item['pics'], true);
                $coverPathUrl = "";
                foreach ($picArr as $k => $imgUrl) {
                    if ($k == 0) {
                        $coverPathUrl = $imgUrl;
                    }
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $coverUrl = $picArr[0];
                $coverHeight = ToolsHelper::getCoverHeight($coverPathUrl);

                $clockInfo = [];
                $clockInfo['id'] = $item['id'];
                $clockInfo['uid'] = $item['uid'];
                $clockInfo['nickname'] = $item['nickname'];
                $clockInfo['avatar'] = ToolsHelper::getLocalImg($item['avatar'], '', 240);
                $clockInfo['title'] = $item['title'];
                $clockInfo['cover'] = $coverUrl;
                $clockInfo['cover_height'] = $coverHeight;
                $clockInfo['price'] = $item['price'];
                $clockInfo['view_num'] = $this->getClockData($item['id'], 'view_num');
                $clockInfo['status'] = $item['status'];
                $clockList[$item['id']] = $clockInfo;
            }
        }

        return $clockList;
    }


    /**
     * 综合推荐打卡列表
     *
     * @param       $uid
     * @param array $params
     * @param       $page
     * @param       $pageSize
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/3/1 18:06
     */
    public function getClockListByTuijain($uid, $params = [], $page, $pageSize)
    {
        $distList = $this->getClockListFromGeoRedis($params['lat'], $params['lng'], 10000);
        $clockArr = [];
        if (!empty($distList)) {
            foreach ($distList as $key => $value) {
                $clockArr[$value[0]] = $value[1] * 1000;
            }
        }


        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ],
                    ]
                ]
            ],
            'sort' => [
                'updated_at' => [
                    'order' => "desc"
                ]
            ]
        ];
        if (!empty($params['keyword'])) {
            $search['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['keyword'],
                    'fields' => ["name", "title", "desc"]
                ]
            ];
        }

        $resultData = EsClock::search($search, $page, $pageSize);
        return $this->formatIndexDataListFromEs($uid, $resultData["hits"]["hits"], $clockArr);
    }

    /**
     * 从Es格式化输出商品列表
     *
     * @param int   $uid
     * @param array $data
     * @param array $clockArr
     * @param int   $isZhiding
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/5/12 15:34
     */
    private function formatIndexDataListFromEs($uid = 0, $data = [], $clockArr = [], $isZhiding = 0)
    {
        $clockList = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $clockInfo = $value['_source'];
                $clockId = $clockInfo['id'];
                $picArr = json_decode($clockInfo['pics'], true);
                foreach ($picArr as $k => $imgUrl) {
                    $picArr[$k] = ToolsHelper::getLocalImg($imgUrl, '', 540);
                }
                $clockInfo['pics'] = $picArr;
                $dist = isset($clockArr[$clockId]) ? $clockArr[$clockId] : 0;
                switch (true) {
                    case $dist > 1000:
                        $clockInfo['dist'] = round($dist / 1000, 1) . "km";
                        break;
                    case $dist <= 1000 && $dist > 200:
                        $clockInfo['dist'] = intval($dist) . '米';
                        break;
                    case $dist <= 200 && $dist > 0:
                        $clockInfo['dist'] = '200米以内';
                        break;
                    default:
                        $clockInfo['dist'] = '';
                }
                // 打卡数据
                $clockData = $this->getClockData($clockId);
                $clockInfo['view_num'] = isset($clockData['view_num']) ? $clockData['view_num'] : 0;
                $clockInfo['tuijian_num'] = isset($clockData['tuijian_num']) ? $clockData['tuijian_num'] : 0;
                $clockInfo['no_tuijian_num'] = isset($clockData['no_tuijian_num']) ? $clockData['no_tuijian_num'] : 0;

                $clockInfo['tuijian'] = $clockInfo['no_tuijian'] = 0;
                if ($uid > 0) {
                    $clockInfo['tuijian'] = $this->getUserTuijianClockData($uid, $clockId, "tuijian");
                    $clockInfo['no_tuijian'] = $this->getUserTuijianClockData($uid, $clockId, "no_tuijian");
                }

                // 是否为热门
                $clockInfo['is_hot'] = $clockInfo['view_num'] >= 100 ? 1 : 0;
                if ($isZhiding) {
                    $clockInfo['is_hot'] = 0;
                }
                $clockInfo['is_zhiding'] = $isZhiding;

                $clockInfo['publish_time'] = ToolsHelper::getTimeStrDiffNow($clockInfo['updated_at']);
                $clockInfo['type'] = 'clock';
                $clockInfo['nickname'] = ToolsHelper::ellipsisStr($clockInfo['nickname'], 12);
                unset($clockInfo['name'], $clockInfo['info'], $clockInfo['status'], $clockInfo['created_at']);
                unset($clockInfo['audit_at'], $clockInfo['audit_reason'], $clockInfo['lat'], $clockInfo['lng'], $clockInfo['updated_at']);
                $clockList[] = $clockInfo;
            }
        }
        return $clockList;
    }


    /**
     * 打卡详情页面数据
     *
     * @param        $clockId
     * @param int    $uid
     * @param string $from
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/9 10:03
     */
    public function getClockPageData($clockId, $uid = 0, $from = '')
    {
        if ($from == 'mycenter') { //用户后台查看文章详情
            $clockInfo = Clock::find()->where(['id' => $clockId])->asArray()->one();
        } else {
            $clockInfo = EsClock::get($clockId);
        }
        if (empty($clockInfo)) {
            return [];
        }

        $clockInfo['pics'] = !empty($clockInfo['pics']) ? json_decode($clockInfo['pics'], true) : [];
        if (!empty($clockInfo['pics'])) {
            foreach ($clockInfo['pics'] as $k => $picUrl) {
                $clockInfo['pics'][$k] = ToolsHelper::getLocalImg($picUrl, '', 540);
            }
        }

        $clockInfo['info'] = str_replace("<p/>","<br>",$clockInfo['info']);
        $clockInfo['tags'] = !empty($clockInfo['tags']) ? json_decode($clockInfo['tags'], true) : [];
        $clockInfo['publish_time'] = $clockInfo['status'] == self::STAUTS_PASS ? ToolsHelper::getTimeStrDiffNow($clockInfo['updated_at']) : '';
        $clockInfo['type'] = 'clock';

        //商品数据
        $clockData = $this->getClockData($clockId);
        $clockInfo['view_num'] = isset($clockData['view_num']) ? $clockData['view_num'] : 0;
        $clockInfo['tuijian_num'] = isset($clockData['tuijian_num']) ? $clockData['tuijian_num'] : 0;
        $clockInfo['no_tuijian_num'] = isset($clockData['no_tuijian_num']) ? $clockData['no_tuijian_num'] : 0;

        // 打卡推荐等级
        $clockInfo['star'] = ToolsHelper::getTuijianLevel($clockInfo['view_num'], $clockInfo['tuijian_num'], $clockInfo['no_tuijian_num']);

        // 是否为热门
        $clockInfo['is_hot'] = $clockInfo['view_num'] >= 100 ? 1 : 0;

        //用户统计数据
        $productService = new ProductService();
        $authorData = $productService->getUserStatisticData($clockInfo['uid']);
        // 我的投票
        $authorData['tuijian'] = $authorData['no_tuijian'] = 0;
        if ($uid > 0) {
            $authorData['tuijian'] = $this->getUserTuijianClockData($uid, $clockId, "tuijian");
            $authorData['no_tuijian'] = $this->getUserTuijianClockData($uid, $clockId, "no_tuijian");
        }


        // 获取用户活跃时间
        $userService = new UserService();
        $activeAt = $userService->getUserStructFromRedis($clockInfo['uid'], 'active_at');
        $clockInfo['active_time'] = intval($activeAt) > 0 ? ToolsHelper::getTimeStrDiffNow($activeAt) : '';

        return array_merge($clockInfo, $authorData);
    }


    /**
     * 获取商品数据
     *
     * @param        $clockId
     * @param string $hashKey
     *
     * @return array|int
     *
     * @author     xudt
     * @date-time  2021/3/20 15:04
     */
    public function getClockData($clockId, $hashKey = '')
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK("clockData", $clockId);
        if (empty($hashKey)) {
            return $redisBaseCluster->hGetAll($redisKey);
        } else {
            return intval($redisBaseCluster->hGet($redisKey, $hashKey));
        }
    }


    /**
     * 获取商品详情
     *
     * @param $clockId
     *
     * @return array|\yii\db\ActiveRecord|null
     *
     * @author     xudt
     * @date-time  2021/3/15 18:00
     */
    public function getClockInfo($clockId)
    {
        $clockInfo = Clock::find()->select(['id', 'uid', 'name', 'title', 'pics', 'price'])->where(['id' => $clockId])->asArray()->one();
        if (empty($clockInfo)) {
            return [];
        }
        $picArr = json_decode($clockInfo['pics'], true);
        $clockInfo['cover'] = ToolsHelper::getLocalImg($picArr[0], '', 240);
        unset($clockInfo['pics']);
        return $clockInfo;
    }

    /**
     * 增加浏览数据
     *
     * @param $clockId
     *
     * @return int
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/3/9 10:46
     */
    public function incrViewClockData($clockId)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('clockData', $clockId);
        $res = $redisBaseCluster->hIncrBy($redisKey, 'view_num', 1);
        return $res;
    }

    /**
     * 猜您喜欢宝贝列表
     *
     * @param $clockId
     * @param $limit
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/4/20 16:21
     */
    public function getGuessLoveClockList($clockId, $limit = 20)
    {
        // 从ES中查找
        $search = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'term' => [
                                'status' => self::STAUTS_PASS
                            ]
                        ],
                    ],
                    'must_not' => [
                        [
                            'term' => [
                                'id' => $clockId
                            ]
                        ]
                    ],
                ]
            ],
            'sort' => [
                'view_num' => [
                    'order' => 'desc'
                ],
                'updated_at' => [
                    'order' => "desc"
                ]
            ]
        ];

        $resultData = EsClock::search($search, 1, $limit);
        return $this->formatDataListFromEs($resultData["hits"]["hits"]);
    }

    /**
     * 获取用户的打卡列表
     *
     * @param $authorData
     * @param $page
     * @param $pageSize
     *
     * @return array|\yii\db\ActiveRecord[]
     *
     * @author     xudt
     * @date-time  2021/7/21 20:55
     */
    public function getUserClockList($authorData, $page, $pageSize)
    {
        $start = ($page - 1) * $pageSize;
        $clockList = Clock::find()->select(['id', 'title', 'pics', 'price'])->where(['uid' => $authorData['uid']])->andWhere(['status' => self::STAUTS_PASS])->offset($start)->limit($pageSize)->orderBy('updated_at desc')->asArray()->all();
        if (!empty($clockList)) {
            foreach ($clockList as $key => &$value) {
                $picArr = json_decode($value['pics'], true);
                $value['cover'] = isset($picArr[0]) ? ToolsHelper::getLocalImg($picArr[0]) : '';
                $value['nickname'] = $authorData['nickname'];
                $value['avatar'] = $authorData['avatar'];
                $value['status'] = self::STAUTS_PASS;
                $value['view_num'] = $this->getClockData($value['id'], 'view_num');
            }
        }
        return $clockList;
    }

    /**
     * 删除打卡
     *
     * @param $userInfo
     * @param $clockId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:49
     */
    public function deleteClock($userInfo, $clockId)
    {
        $now = time();
        if (empty($clockId)) {
            return ToolsHelper::funcReturn("参数错误");
        }

        $clockModel = Clock::find()->where(['id' => $clockId])->one();
        if ($clockModel->uid != $userInfo['uid']) {
            return ToolsHelper::funcReturn("非法操作");
        }

        $status = self::STATUS_DEL;
        $clockModel->status = $status;
        $clockModel->updated_at = $now;
        if ($clockModel->save()) {
            $clockEsData = EsClock::get($clockId);
            if (!empty($clockEsData)) {
                if (EsClock::update($clockId, ['status' => $status])) {
                }
            }
            return ToolsHelper::funcReturn("操作成功", true, ['status' => $status]);
        }
        return ToolsHelper::funcReturn("操作失败");
    }

    /**
     * 打卡推荐/不推荐
     *
     * @param $userInfo
     * @param $clockId
     * @param $tuijian
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/2/27 16:49
     */
    public function tuijianClock($userInfo, $clockId, $tuijian)
    {
        if (empty($tuijian) || empty($clockId)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        $now = time();
        if ($tuijian == 1) {
            $type = "tuijian";
            $fieldName = "tuijian_num";
        } else {
            $type = "no_tuijian";
            $fieldName = "no_tuijian_num";
        }

        // 用户是否已评价
        $isTuijainOver = $this->getUserTuijianClockData($userInfo['uid'], $clockId, "tuijian");
        $isNoTuijainOver = $this->getUserTuijianClockData($userInfo['uid'], $clockId, "no_tuijian");
        if($isTuijainOver || $isNoTuijainOver){
            return ToolsHelper::funcReturn("您已评价过，非常感谢您的评价！");
        }

        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $userRedisKey = RedisHelper::RK('userClockData', $type, $userInfo['uid']);
        if ($redisBaseCluster->zAdd($userRedisKey, $now, $clockId)) {
            $redisKey = RedisHelper::RK('clockData', $clockId);
            $redisBaseCluster->hIncrBy($redisKey, $fieldName, 1);

            return ToolsHelper::funcReturn("评价成功，非常感谢您的评价！", true);
        }
        return ToolsHelper::funcReturn("评价失败");
    }

    /**
     * 用户是否推荐或不推荐本打卡
     *
     * @param        $uid
     * @param        $clockId
     * @param string $type
     *
     * @return int
     *
     * @author     xudt
     * @date-time  2021/7/29 13:44
     */
    public function getUserTuijianClockData($uid, $clockId, $type = "tuijian")
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $userRedisKey = RedisHelper::RK('userClockData', $type, $uid);
        $res = $redisBaseCluster->zScore($userRedisKey, $clockId);
        if ($res > 0) {
            return 1;
        }
        return 0;
    }
}