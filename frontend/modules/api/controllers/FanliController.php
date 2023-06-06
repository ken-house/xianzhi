<?php
/**
 * 订单返利
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/10/13 21:45
 */

namespace frontend\modules\api\controllers;

use common\helpers\ToolsHelper;
use common\services\BusinessProductService;
use common\services\FanliService;
use Yii;

class FanliController extends BaseController
{
    const PAGESIZE = 20;

    /**
     * 订单号申请返利
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/13 23:26
     */
    public function actionOrderApply()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $orderSn = trim(Yii::$app->request->post("order_sn", ""));
        if (empty($orderSn)) {
            return ToolsHelper::funcReturn("请输入订单号");
        }

        $fanliService = new FanliService();
        return $fanliService->orderApply($uid, $orderSn);
    }

    /**
     * 订单列表
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/10/14 15:14
     */
    public function actionOrderList()
    {
        $userInfo = Yii::$app->params['userRedis'];
        $uid = $userInfo['uid'];

        $keyword = Yii::$app->request->get("keyword", '');
        $page = Yii::$app->request->get('page', 1);
        $pageSize = self::PAGESIZE;

        $fanliService = new FanliService();
        $orderList = $fanliService->getOrderList($uid, $keyword, $page, $pageSize);
        return ToolsHelper::funcReturn(
            '订单列表',
            true,
            [
                'userInfo' => $userInfo,
                'orderList' => $orderList,
                'page' => $page,
                'pageSize' => $pageSize,
            ]
        );
    }
}