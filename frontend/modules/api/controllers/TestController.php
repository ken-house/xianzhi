<?php
/**
 * 测试
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/1/7 15:16
 */

namespace frontend\modules\api\controllers;

use common\helpers\RedisHelper;
use common\helpers\ToolsHelper;
use common\models\BusinessProduct;
use common\models\elasticsearch\EsBusinessProduct;
use common\models\elasticsearch\EsProduct;
use common\models\elasticsearch\EsProductCopy;
use common\models\mongo\MongodbMessageRecord;
use common\models\mongo\MongodbProductCommentRecord;
use common\models\mongo\MongodbRewardPointRecord;
use common\models\Product;
use common\models\ProductCategory;
use common\models\SearchKeyword;
use common\models\User;
use common\models\UserData;
use common\services\GroupBuyService;
use common\services\MessageService;
use common\services\OfficialAccountService;
use common\services\PddService;
use common\services\RewardPointService;
use common\services\UserService;
use common\services\WechatService;
use console\services\jobs\MessageJob;
use Yii;

class TestController extends BaseController
{
    public function actionTest()
    {
        die;
//        获取拼多多活动地址
        $url = "https://mobile.yangkeduo.com/promotion_op.html?type=27&id=171902&pid=25207992_220301171&cpsSign=CM_211021_25207992_220301171_58efb9937e9191a893a6864345d459d2&duoduo_type=2";
        $pddService = new PddService();
        $data = $pddService->getPromotionActivityUrl(39998,$url);
        echo "<pre>"; print_r($data);
        die;
//        $searchKeywordArr = SearchKeyword::find()->select(['keyword', 'count(*) c'])->where(['>','uid',100001])->groupBy("keyword")->orderBy("c desc")->asArray()->all();
//        foreach ($searchKeywordArr as $key => &$value) {
//            if (intval($value['keyword']) > 0) { // 分类
//                $value['category_name'] = ProductCategory::find()->select(['category_name'])->where(['id' => intval($value['keyword'])])->scalar();
//            }
//        }
//        echo "<pre>";
//        print_r($searchKeywordArr);
//        die;

//
//        die;


        // 分类的图标
        $categoryArr = ProductCategory::find()->where(['category_level' => 3])->andWhere(['>=', 'id', 1893])->asArray()->all();
        foreach ($categoryArr as $key => $value) {
            $pid = $value['pid'];
            $ppid = ProductCategory::find()->select(['pid'])->andWhere(['id' => $pid])->scalar();
            $iconUrl = '/images/category/' . $ppid . '/' . $pid . '/' . $value['id'] . '.jpg';
            ProductCategory::updateAll(['icon' => $iconUrl], ['id' => $value['id']]);
        }
        die;

        // 商品分类
        $name = Yii::$app->request->get("name", '');
        $categoryId = ProductCategory::find()->select(['id'])->where(['category_name' => $name, 'category_level' => 3])->scalar();
        $res = Product::updateAll(
            ['category' => $categoryId],
            [
                'AND',
                ['category' => 0],
                ['status' => 1],
                [
                    'OR',
                    ['LIKE', 'title', $name],
                    ['LIKE', 'name', $name]
                ]
            ]
        );
        var_dump($res);
        die;


        $officialAccount = Yii::$app->request->get("openid");
        $officialAccountService = new OfficialAccountService();
        $userInfo = $officialAccountService->getUserInfo($officialAccount);
        echo "<pre>";
        print_r($userInfo);
        die;
//        $appId = "wx2c2c44762e2fa049";
//        $appSecret = "d3edf1ee7f8b8e040a229b9621dca2df";
//        $redirectUrl = urlencode("https://test-api.xiaozhatravel.top/api/test/wx");
//        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appId . "&redirect_uri=" . $redirectUrl . "&response_type=code&scope=snsapi_userinfo&state=111#wechat_redirec";
//        echo $url;
//        die;


        $uid = Yii::$app->request->get("uid");
        //读取消息列表
        $messageService = new MessageService($uid);
        $messageResult = $messageService->getList(1, 500);
        echo "<pre>";
        print_r($messageResult['messageList']);
        die;

        // 积分记录
        $recordList = RewardPointService::getRewardPointRecord($uid, 0, 1, 500);
        echo "<pre>";
        print_r($recordList);

        // 数据库的积分数
        $rewardPoint = UserData::find()->select(['reward_point'])->where(['uid' => $uid])->scalar();
        var_dump($rewardPoint);

        // redis的积分数
        $userService = new UserService();
        $userInfo = $userService->getUserInfoFromRedis($uid);
        echo "<pre>";
        print_r($userInfo);
        die;


//        $search = [
//            'query' => [
//                "match_all" => (object)[]
//            ],
//            'sort' => [
//                'updated_at' => [
//                    'order' => "desc"
//                ]
//            ]
//        ];
//        $data = EsProduct::search($search);
//        echo "<pre>"; print_r($data);
//        die;
//
//
//        $params = [
//            'index' => 'xianzhi_product_2',
//            'body' => $search
//        ];
//
//        /** @var Client $client */
//        $client = Yii::$app->get('es')->getClient();
//        $response = $client->search($params);
//        echo "<pre>"; print_r($response);
//        die;


//        $now = time();
//        $type = Yii::$app->request->get('type');
//        $uid = Yii::$app->request->get('uid');
//        $id = Yii::$app->request->get('id');
//
//        /** @var \redisCluster $redisBaseCluster */
//        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
//        $redisKey = RedisHelper::RK("userProductData", $type, $uid);
//        $res = $redisBaseCluster->zAdd($redisKey,$now,$id);
//        var_dump($res);
//        die;
//        $productModel = new Product();
//        $productModel->info = "1111";
//        $productModel->uid = 1;
//        $productModel->pics = '1111';
//        $productModel->status = 0;
//        $productModel->price = 100;
//        $productModel->trade_status = 0;
//        $res = $productModel->save();
//        var_dump($productModel->getErrors());
//        var_dump($res);
//        die;

//        /** @var \Redis $redis */
//        $redis = Yii::$app->get('redisBase');
//        $res = $redis->get('test');
//        var_dump($res);
//        die;

        /** @var \Elasticsearch\Client $client */
        $client = Yii::$app->get('es')->getClient();
        $response = $client->indices();
        $result = $response->getMapping();
        echo "<pre>";
        print_r($result);
        die;
    }

    /**
     * 公众号回调
     *
     *
     * @author     xudt
     * @date-time  2021/6/29 10:30
     */
//    public function actionWx()
//    {
//        $code = Yii::$app->request->get("code");
//
//        $appId = "wx2c2c44762e2fa049";
//        $appSecret = "d3edf1ee7f8b8e040a229b9621dca2df";
//
//        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $appSecret . "&code=" . $code . "&grant_type=authorization_code";
//        $responseData = ToolsHelper::sendRequest($url, "GET");
//
//        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$responseData['access_token']."&openid=".$responseData['openid']."&lang=zh_CN";
//        $result = ToolsHelper::sendRequest($url, "GET");
//        echo "<pre>";
//        print_r($result);
//        die;
//    }

    /**
     * 测试公众号发送
     *
     *
     * @author     xudt
     * @date-time  2021/6/29 10:57
     */
//    public function actionSend()
//    {
//        $openId = Yii::$app->request->get("openId");
//
//        $appId = "wx2c2c44762e2fa049";
//        $appSecret = "d3edf1ee7f8b8e040a229b9621dca2df";
//
//        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
//        $responseData = ToolsHelper::sendRequest($url, "GET");
//        if (!isset($responseData['access_token'])) {
//            echo "access_token 获取失败";
//            die;
//        }
//        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $responseData['access_token'];
//        $data = [
//            'touser' => $openId,
//            'template_id' => 'm6zVnpfeNWkVAiMOQeuRITiTHfVdW3tSreqBr7rB9Jo',
//            'url' => '',
//            'miniprogram' => [
//                'appid' => 'wxe1ac8e07ccb42255',
//                'pagepath' => '/pages/product/product?id=1962',
//            ],
//            'data' => [
//                'first' => [
//                    'value' => '您的闲置物品有人想要，请及时处理。',
//                    'color' => '#173177',
//                ],
//                'keyword1' => [
//                    'value' => '面包机',
//                    'color' => '#173177',
//                ],
//                'keyword2' => [
//                    'value' => '10.00元',
//                    'color' => '#173177',
//                ],
//                'keyword3' => [
//                    'value' => '13088886666',
//                    'color' => '#173177',
//                ],
//                'keyword4' => [
//                    'value' => '2014年7月21日 18:36',
//                    'color' => '#173177',
//                ],
//                'remark' => [
//                    'value' => '感谢您的使用，祝您生活愉快。',
//                    'color' => '#173177',
//                ],
//            ],
//        ];
//        $responseData = ToolsHelper::sendRequest($url, "POST", $data);
//        echo "<pre>";
//        print_r($responseData);
//        die;
//    }

    /**
     * 获取基本信息
     *
     *
     * @author     xudt
     * @date-time  2021/6/29 16:40
     */
//    public function actionInfo(){
//        $openId = Yii::$app->request->get("openId");
//
//        $appId = "wx2c2c44762e2fa049";
//        $appSecret = "d3edf1ee7f8b8e040a229b9621dca2df";
//
//        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
//        $responseData = ToolsHelper::sendRequest($url, "GET");
//        if (!isset($responseData['access_token'])) {
//            echo "access_token 获取失败";
//            die;
//        }
//        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$responseData['access_token']."&openid=".$openId."&lang=zh_CN";
//        $responseData = ToolsHelper::sendRequest($url, "GET");
//        echo "<pre>";
//        print_r($responseData);
//        die;
//    }
}