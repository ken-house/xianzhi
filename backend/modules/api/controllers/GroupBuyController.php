<?php
/**
 * 团购
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/25 11:25
 */

namespace backend\modules\api\controllers;

use backend\services\GroupBuyService;
use common\helpers\ToolsHelper;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class GroupBuyController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        if (YII_ENV_DEV) {
            return parent::behaviors();
        } else {
            return ArrayHelper::merge(
                [
                    'access' => [
                        'class' => AccessControl::class,
                        'rules' => [
                            [
                                'allow' => true,
                                'roles' => ['@'],
                            ]
                        ],
                    ],
                ],
                parent::behaviors()
            );
        }
    }

    /**
     * 团购商品列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 16:45
     */
    public function actionProductList()
    {
        $data['id'] = Yii::$app->request->get('id', 0);
        $data['title'] = Yii::$app->request->get('title', "");
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $groupBuyService = new GroupBuyService();
        $result = $groupBuyService->getProductList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyProductStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['headerData'] = $this->getHeaderData();

        return $result;
    }

    /**
     * 更改团购活动状态
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 16:52
     */
    public function actionProductStatus()
    {
        $id = Yii::$app->request->post("id", 0);
        $status = Yii::$app->request->post("status", 0);

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->updateProductStatus($id, $status);
    }

    /**
     * 团购详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 17:14
     */
    public function actionProductInfo()
    {
        $id = Yii::$app->request->get("id", 0);

        $refundTypeList = Yii::$app->params['groupBuyRefundType'];
        if (empty($id)) {
            return ToolsHelper::funcReturn(
                "团购详情",
                true,
                [
                    'productInfo' => [
                        'id' => 0,
                        'pics' => [],
                    ],
                    'refundTypeList' => ToolsHelper::convertSelectOptionArr($refundTypeList),
                ]
            );
        }

        $groupBuyService = new GroupBuyService();
        $productInfo = $groupBuyService->getProductInfo($id);

        return ToolsHelper::funcReturn(
            "团购详情",
            true,
            [
                'productInfo' => $productInfo,
                'refundTypeList' => ToolsHelper::convertSelectOptionArr($refundTypeList),
            ]
        );
    }


    /**
     * 添加/编辑团购活动
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/12/1 16:56
     */
    public function actionProductSave()
    {
        $data = Yii::$app->request->post();

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->saveProduct($data);
    }


    /**
     * 表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/10 16:45
     */
    private function getHeaderData()
    {
        return [
            'id' => 'ID',
            'shop_name' => '商家名称',
            'product_title' => '标题',
            'sub_title' => '小标题',
            'pics' => '图片',
            'price' => '默认价格',
            'activity_time' => '活动时间',
            'limit_num' => '限量',
            'sale_num' => '已售',
            'shop_commission_rate' => '商家佣金比例',
            'is_distribute' => '分销',
            'commission_rate' => '佣金比例',
            'refund_type' => '退款类型',
            'status_name' => '状态',
        ];
    }


    /**
     * 店铺列表
     *
     * @return mixed
     *
     * @author     xudt
     * @date-time  2021/12/1 17:19
     */
    public function actionShopList()
    {
        $data['id'] = Yii::$app->request->get('id', 0);
        $data['keyword'] = Yii::$app->request->get('keyword', "");
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $groupBuyService = new GroupBuyService();
        $result = $groupBuyService->getShopList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyShopStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['headerData'] = $this->getShopHeaderData();

        return $result;
    }

    /**
     * 店铺详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:03
     */
    public function actionShopInfo()
    {
        $id = Yii::$app->request->get("id", 0);
        if (empty($id)) {
            return ToolsHelper::funcReturn(
                "商家详情",
                true,
                [
                    'shopInfo' => [
                        'id' => 0,
                        'pics' => [],
                    ]
                ]
            );
        }
        $groupBuyService = new GroupBuyService();
        $shopInfo = $groupBuyService->getShopInfo($id);

        return ToolsHelper::funcReturn(
            "商家详情",
            true,
            [
                'shopInfo' => $shopInfo,
            ]
        );
    }

    /**
     * 更改店铺状态
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 16:52
     */
    public function actionShopStatus()
    {
        $id = Yii::$app->request->post("id", 0);
        $status = Yii::$app->request->post("status", 0);

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->updateShopStatus($id, $status);
    }

    /**
     * 新增/修改商家
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:07
     */
    public function actionShopSave()
    {
        $data = Yii::$app->request->post();

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->saveShop($data);
    }


    /**
     * 店铺管理员列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:15
     */
    public function actionManagerList()
    {
        $shopId = Yii::$app->request->get("id", 0);

        $groupBuyService = new GroupBuyService();
        $list = $groupBuyService->getShopManagerList($shopId);
        return ToolsHelper::funcReturn(
            "店铺管理员列表",
            true,
            [
                'list' => $list,
                'headerData' => $this->getShopManagerHeaderData()
            ]
        );
    }

    /**
     * 表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/10 16:45
     */
    private function getShopManagerHeaderData()
    {
        return [
            'uid' => 'UID',
            'avatar' => '头像',
            'nickname' => '昵称',
            'phone' => '联系电话',
            'role_name' => '角色',
            'status_name' => '状态',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 商家状态修改
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:20
     */
    public function actionManagerStatus()
    {
        $id = Yii::$app->request->post("id", 0);
        $status = Yii::$app->request->post("status", 0);
        $groupBuyService = new GroupBuyService();
        return $groupBuyService->managerStatus($id, $status);
    }

    /**
     * 商家管理员添加
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:30
     */
    public function actionManagerAdd()
    {
        $data = Yii::$app->request->post();

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->managerAdd($data);
    }

    /**
     * 团购分销店主
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 20:52
     */
    public function actionDspList()
    {
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $groupBuyService = new GroupBuyService();
        $result = $groupBuyService->getDspList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyDspStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['headerData'] = $this->getDspHeaderData();

        return $result;
    }

    /**
     * 分销店主审核
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 21:01
     */
    public function actionDspStatus()
    {
        $id = Yii::$app->request->post('id');
        $reason = Yii::$app->request->post("reason", '');
        $action = Yii::$app->request->post("action", 'pass'); // pass  refuse close

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->dspAudit($id, $reason, $action);
    }

    /**
     * 分销店主表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/12/1 21:07
     */
    private function getDspHeaderData()
    {
        return [
            'id' => 'ID',
            'uid' => '用户uid',
            'nickname' => '用户昵称',
            'phone' => '手机号',
            'order_num' => '订单数',
            'total_income' => '预计总收入',
            'settle_amount' => '已结算',
            'withdraw_amount' => '已提现',
            'audit_at' => '审核时间',
            'status_name' => '状态',
        ];
    }


    /**
     * 表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/10 16:45
     */
    private function getShopHeaderData()
    {
        return [
            'id' => 'ID',
            'shop_name' => '商家名称',
            'shop_avatar' => '商家头像',
            'phone' => '联系电话',
            'location' => '位置',
            'info' => '简介',
            'pics' => '图片',
            'avg_price' => '人均价格',
            'score' => '评分',
            'order_num' => '订单数',
            'total_income' => '预计总收入',
            'settle_amount' => '已结算',
            'withdraw_amount' => '已提现',
            'withdraw_account' => '提现账户',
            'commission_rate' => '佣金比例',
            'updated_at' => '更新时间',
            'status_name' => '状态',
        ];
    }

    /**
     * 订单列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 21:35
     */
    public function actionOrderList()
    {
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $data['dsp_uid'] = Yii::$app->request->get('dsp_uid', 0);
        $data['shop_id'] = Yii::$app->request->get('shop_id', 0);
        $data['order_no'] = Yii::$app->request->get('order_no', '');
        $data['status'] = Yii::$app->request->get('status', -1);
        $data['keyword'] = Yii::$app->request->get('keyword', '');
        $startDate = Yii::$app->request->get('start_date', date('Y-m-d'));
        $data['start_date'] = !empty($startDate) ? strtotime($startDate) : 0;
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));
        $data['end_date'] = !empty($endDate) ? strtotime($endDate) + 86399 : 0;
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $groupBuyService = new GroupBuyService();
        $result = $groupBuyService->getOrderList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyOrderStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['headerData'] = $this->getOrderHeaderData();

        return $result;
    }

    /**
     * 订单表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/12/2 11:41
     */
    private function getOrderHeaderData()
    {
        return [
            'id' => 'ID',
            'order_no' => '订单号',
            'product_cover' => '商品封面',
            'product_title' => '商品标题',
            'order_amount' => '订单金额',
            'shop_order_amount' => '商家订单金额',
            'order_price' => '单价',
            'order_num' => '数量',
            'refund_type' => '退款类型',
            'remark' => '备注',
            'nickname' => '购买者',
            'dsp_nickname' => '分销店主',
            'commission_amount' => '预估佣金',
            'pay_at' => '付款时间',
            'finish_nickname' => '核销管理员',
            'finish_at' => '核销时间',
            'refund_apply_at' => '申请退款时间',
            'refund_finish_at' => '退款完成时间',
            'status_name' => '状态',
        ];
    }


    /**
     * 订单退款
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/25 11:30
     */
    public function actionOrderRefund()
    {
        $orderId = Yii::$app->request->post("id");
        $reason = Yii::$app->request->post("reason", "");
        $type = Yii::$app->request->post("type", 'pass'); // pass 同意退款 refuse 拒绝退款

        $groupBuyService = new GroupBuyService();
        if ($type == 'pass') { // 同意退款
            return $groupBuyService->orderRefund($orderId, $reason);
        }
        // 拒绝退款
        return $groupBuyService->orderRefundRefuse($orderId, $reason);
    }

    /**
     * 提现列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/7 18:42
     */
    public function actionWithdrawList()
    {
        $data['uid'] = Yii::$app->request->get('uid', 0);
        $data['shop_id'] = Yii::$app->request->get('shop_id', 0);
        $data['status'] = Yii::$app->request->get('status', -1);
        $startDate = Yii::$app->request->get('start_date', date('Y-m-d'));
        $data['start_date'] = !empty($startDate) ? strtotime($startDate) : 0;
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));
        $data['end_date'] = !empty($endDate) ? strtotime($endDate) + 86399 : 0;
        $data['page'] = Yii::$app->request->get('page', 1);
        $data['page_size'] = Yii::$app->request->get('page_size', 10);

        $groupBuyService = new GroupBuyService();
        $result = $groupBuyService->getWithdrawList($data);

        $statusList = ['-1' => '全部'] + Yii::$app->params['groupBuyWithdrawStatus'];
        $result['data']['statusList'] = ToolsHelper::convertSelectOptionArr($statusList);
        $result['data']['headerData'] = $this->getWithdrawHeaderData();

        return $result;
    }

    /**
     * 提现列表表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/12/2 11:41
     */
    private function getWithdrawHeaderData()
    {
        return [
            'id' => 'ID',
            'nickname' => '用户',
            'shop_name' => '商户名',
            'amount' => '提现金额',
            'current_amount' => '可提现余额',
            'created_at' => '申请时间',
            'audit_at' => '审核时间',
            'status_name' => '状态',
        ];
    }

    /**
     * 提现审核
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/7 19:04
     */
    public function actionWithdrawStatus()
    {
        $id = Yii::$app->request->post('id', 0);
        $action = Yii::$app->request->post('action', 'pass');
        $reason = Yii::$app->request->post('reason', '');

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->withdrawStatus($id, $action, $reason);
    }


    /**
     * 商品购买选项列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:15
     */
    public function actionProductOptionList()
    {
        $productId = Yii::$app->request->get("id", 0);

        $groupBuyService = new GroupBuyService();
        $list = $groupBuyService->getProductOptionList($productId);
        return ToolsHelper::funcReturn(
            "商品购买选项列表",
            true,
            [
                'list' => $list,
                'headerData' => $this->getProductOptionHeaderData()
            ]
        );
    }

    /**
     * 表头
     *
     * @return string[]
     *
     * @author     xudt
     * @date-time  2021/6/10 16:45
     */
    private function getProductOptionHeaderData()
    {
        return [
            'id' => 'ID',
            'name' => '购买选项名',
            'price' => '价格',
            'original_price' => '原价',
            'max_num' => '限量',
            'status_name' => '状态',
            'sort' => '排序',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 购买选项状态详情
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:20
     */
    public function actionProductOptionInfo()
    {
        $id = Yii::$app->request->get("id", 0);
        $groupBuyService = new GroupBuyService();
        $optionInfo = $groupBuyService->getProductOptionInfo($id);
        return ToolsHelper::funcReturn(
            "购买项详情",
            true,
            [
                'optionInfo' => $optionInfo
            ]
        );
    }

    /**
     * 购买选项状态修改
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:20
     */
    public function actionProductOptionStatus()
    {
        $id = Yii::$app->request->post("id", 0);
        $status = Yii::$app->request->post("status", 0);
        $groupBuyService = new GroupBuyService();
        return $groupBuyService->productOptionStatus($id, $status);
    }

    /**
     * 购买选项保存
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/12/1 19:30
     */
    public function actionProductOptionSave()
    {
        $data = Yii::$app->request->post();

        $groupBuyService = new GroupBuyService();
        return $groupBuyService->productOptionSave($data);
    }
}