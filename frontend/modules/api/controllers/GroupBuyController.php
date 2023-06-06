<?php
/**
 * 超值团购
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/15 11:49
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BannerService;
use common\services\GroupBuyService;
use Yii;

class GroupBuyController extends BaseController
{
    const PAGESIZE = 20;


    /**
     * 团购商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 10:41
     */
    public function actionIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $params['uid'] = $userInfo['uid'];
        $params['is_distribute'] = Yii::$app->request->get('is_distribute', -1); // -1 不限 0 非分销商品 1分销商品
        $params['keyword'] = Yii::$app->request->get('keyword', ''); // 搜索关键词
        $params['lat'] = Yii::$app->request->get('lat', 0);
        $params['lng'] = Yii::$app->request->get('lng', 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $groupBuyService = new GroupBuyService();
        $productList = $groupBuyService->getProductList($params, $page, $pageSize);

        // banner列表
        $bannerService = new BannerService();
        $bannerList = $bannerService->getBannerList($params['lat'], $params['lng'], BannerService::BANNER_GROUP_BUY);

        // 导航
        $navList = Yii::$app->params['groupBuyNavList'];

        return ToolsHelper::funcReturn(
            "商品列表",
            true,
            [
                'userInfo' => $userInfo,
                'productList' => $productList,
                'bannerList' => $bannerList,
                'navList' => $navList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 店铺主页
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/20 11:32
     */
    public function actionShop()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $shopId = Yii::$app->request->get("id", 0);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $groupBuyService = new GroupBuyService();
        // 商家详情
        $shopInfo = $groupBuyService->getShopInfo($shopId);
        if (empty($shopInfo)) {
            return ToolsHelper::funcReturn("参数错误");
        }
        // 商家商品列表
        $productList = $groupBuyService->getShopProductList($shopId, GroupBuyService::PRODUCT_PASS_STATUS, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "商家主页",
            true,
            [
                'userInfo' => $userInfo,
                'shopInfo' => $shopInfo,
                'productList' => $productList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 团购详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 11:47
     */
    public function actionInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productId = Yii::$app->request->get('id', 0);
        $optionId = Yii::$app->request->get('option_id', 0);

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->getProductInfo($userInfo, $productId, $optionId);
    }

    /**
     * 下单支付
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 14:54
     */
    public function actionPay()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $productId = Yii::$app->request->post('id', 0);
        $optionId = Yii::$app->request->post('option_id', 0);
        $orderNum = Yii::$app->request->post('order_num', 1); // 数量
        $remark = Yii::$app->request->post('remark', ''); //备注
        $inviteCode = Yii::$app->request->post('invite_code', 0); // 分销商用户uid


        $groupBuyService = new GroupBuyService();
        return $groupBuyService->orderPay($userInfo, $productId, $optionId, $orderNum, $remark, $inviteCode);
    }

    /**
     * 订单列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 19:06
     */
    public function actionOrderList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $keyword = Yii::$app->request->get("keyword", ''); // 搜索关键词
        $type = Yii::$app->request->get('type', 0); // 0 用户订单 1 分销佣金订单/商家订单
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $groupBuyService = new GroupBuyService();
        $overviewData = [];
        if (!empty($type)) {
            $overviewData = $groupBuyService->getOverviewData($uid);
        }
        if (!empty($overviewData['role_name']) && $overviewData['role_name'] == "商家") {
            $type = 2;
        }
        $orderList = $groupBuyService->getOrderList($uid, $type, $keyword, $page, $pageSize);


        return ToolsHelper::funcReturn(
            "订单列表",
            true,
            [
                'userInfo' => $userInfo,
                'orderList' => $orderList,
                'overviewData' => $overviewData,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * 订单详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/23 22:05
     */
    public function actionOrderInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderId = Yii::$app->request->get("id", 0);

        $groupBuyService = new GroupBuyService();
        $orderRes = $groupBuyService->getOrderInfo($uid, $orderId);
        if ($orderRes['result']) {
            $orderRes['data']['sign'] = ToolsHelper::getOrderSign($uid, $orderId);
        }
        return $orderRes;
    }

    /**
     * 生成核验二维码
     *
     *
     * @author     xudt
     * @date-time  2021/11/24 15:18
     */
    public function actionOrderErweima()
    {
        $orderId = Yii::$app->request->get("id", 0);
        $sign = Yii::$app->request->get("sign", 0);

        $groupBuyService = new GroupBuyService();
        $url = $groupBuyService->getOrderCheckUrl($orderId, $sign);

        $qrcode = new \QRcode();
        echo $qrcode->png($url, false, 'L', 6, 1);//调用png()方法生成二维码
        die;
    }

    /**
     * 核验订单详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 15:37
     */
    public function actionCheckInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderId = Yii::$app->request->post("id", 0);
        $timestamp = Yii::$app->request->post("timestamp", 0);
        $secretKey = Yii::$app->request->post("secret_key", "");

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->getOrderCheckInfo($uid, $orderId, $timestamp, $secretKey);
    }


    /**
     * 订单核销（店铺管理员操作）
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 21:51
     */
    public function actionOrderFinish()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderId = Yii::$app->request->post('id', 0); // 订单id

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->orderFinish($uid, $orderId);
    }

    /**
     * 订单删除
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 18:36
     */
    public function actionOrderDelete()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderId = Yii::$app->request->post('id', 0); // 订单id

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->orderDelete($uid, $orderId);
    }

    /**
     * 继续支付
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/24 18:54
     */
    public function actionContinuePay()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $orderId = Yii::$app->request->post('id', 0); // 订单id

        // 获取订单详情
        $groupBuyService = new GroupBuyService();
        return $groupBuyService->orderPayContinue($userInfo, $orderId);
    }


    /**
     * 订单申请退款/取消申请退款
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/16 22:02
     */
    public function actionApplyRefund()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderId = Yii::$app->request->post('id', 0); // 订单id
        $action = Yii::$app->request->post('action', 0); // 0 申请退款 1 取消申请退款

        $groupBuyService = new GroupBuyService();
        if ($action) {
            return $groupBuyService->cancelOrderApplyRefund($uid, $orderId);
        }
        return $groupBuyService->orderApplyRefund($uid, $orderId);
    }


    /**
     * 分销用户详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/22 22:03
     */
    public function actionDspInfo()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $groupBuyService = new GroupBuyService();
        $dspInfo = $groupBuyService->getDspInfo($userInfo['uid']);
        return ToolsHelper::funcReturn(
            "分销用户信息",
            true,
            [
                'userInfo' => $userInfo,
                'dspInfo' => $dspInfo,
            ]
        );
    }

    /**
     * 申请成为分销商
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/17 15:24
     */
    public function actionDspApply()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->dspApply($userInfo);
    }

    /**
     * 收益概览
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/26 16:29
     */
    public function actionOverview()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $groupBuyService = new GroupBuyService();
        $overviewData = $groupBuyService->getOverviewData($uid);
        return ToolsHelper::funcReturn(
            "收益概览",
            true,
            [
                'userInfo' => $userInfo,
                'overviewData' => $overviewData
            ]
        );
    }

    /**
     * 提现页面
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/28 11:12
     */
    public function actionWithdrawIndex()
    {
        $userInfo = Yii::$app->params['userRedis'];

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->getWithdrawData($userInfo);
    }


    /**
     * 申请提现
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/17 15:11
     */
    public function actionWithdrawApply()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $id = Yii::$app->request->post("id", 0);

        if (!$userInfo['phone']) {
            return ToolsHelper::funcReturn("请先认证手机号", false);
        }

        $groupBuyWithdrawList = Yii::$app->params['groupBuyWithdrawList'];

        $groupBuyService = new GroupBuyService();
        $managerInfo = $groupBuyService->getShopManagerByUid($uid);
        if ($managerInfo) {
            if (empty($managerInfo['role_id'])) { // 普通管理员
                return ToolsHelper::funcReturn("仅超级管理员可申请提现");
            }
            $amount = isset($groupBuyWithdrawList[2][$id]['num']) ? $groupBuyWithdrawList[2][$id]['num'] : 0;
            return $groupBuyService->shopWithdrawApply($uid, $managerInfo['shop_id'], $amount);
        } else {
            $amount = isset($groupBuyWithdrawList[1][$id]['num']) ? $groupBuyWithdrawList[1][$id]['num'] : 0;
            return $groupBuyService->dspWithdrawApply($uid, $amount);
        }
    }

    /**
     * 提现记录
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/29 10:40
     */
    public function actionWithdrawRecord()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $groupBuyService = new GroupBuyService();
        $overviewData = $groupBuyService->getOverviewData($uid);
        $shopId = 0;
        if (!empty($overviewData) && $overviewData['role_name'] == "商家") {
            $shopId = $overviewData['id'];
        }
        $recordList = $groupBuyService->getWithdrawRecordList($uid, $shopId, $page, $pageSize);

        return ToolsHelper::funcReturn(
            "提现记录",
            true,
            [
                'userInfo' => $userInfo,
                'overviewData' => $overviewData,
                'recordList' => $recordList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }


}