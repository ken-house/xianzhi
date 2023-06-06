<?php
/**
 * 公众平台服务类
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/8/18 20:20
 */

namespace common\services;

use common\helpers\ToolsHelper;
use common\models\UnionOpenid;
use Yii;

class UnionService
{
    /**
     * 保存公众号openid
     *
     * @param $officialOpenid
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/8/18 20:31
     */
    public function saveUnionRecord($officialOpenid)
    {
        $officialOpenid = strval($officialOpenid);
        $now = time();
        $secretKey = md5($officialOpenid . $now);
        $unionOpenidModel = UnionOpenid::find()->where(['official_openid' => $officialOpenid])->one();
        if (empty($unionOpenidModel)) {
            $unionOpenidModel = new UnionOpenid();
            $unionOpenidModel->official_openid = $officialOpenid;
        } elseif (!empty($unionOpenidModel->wx_openid)) {
            return "已绑定小程序帐号，无需获取动态码";
        }
        $unionOpenidModel->secret_key = $secretKey;
        $unionOpenidModel->created_at = $now;
        if ($unionOpenidModel->save()) {
            return $secretKey;
        }
        return "获取动态码失败，请重新获取或联系微信客服";
    }

    /**
     * 取消关注，取消关联
     *
     * @param $officialOpenid
     *
     * @return string
     *
     * @author     xudt
     * @date-time  2021/8/21 14:45
     */
    public function unSubscribe($officialOpenid)
    {
        $officialOpenid = strval($officialOpenid);
        $unionOpenidModel = UnionOpenid::find()->where(['official_openid' => $officialOpenid])->one();
        if(!empty($unionOpenidModel)){
            $unionOpenidModel->secret_key = "";
            $unionOpenidModel->wx_openid = "";
            $unionOpenidModel->updated_at = 0;
            $unionOpenidModel->save();
        }
        return "";
    }

    /**
     * 关联公众号与小程序
     *
     * @param $secretKey
     * @param $wxOpenid
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/8/18 22:39
     */
    public function unionRelation($secretKey, $wxOpenid)
    {
        $unionOpenidModel = UnionOpenid::find()->where(['secret_key' => $secretKey])->one();
        if (empty($unionOpenidModel)) {
            return ToolsHelper::funcReturn("绑定失败，密钥错误或已失效");
        }
        if (!empty($unionOpenidModel->wx_openid)) {
            return ToolsHelper::funcReturn("绑定失败，不可重复绑定");
        }
        $unionOpenidModel->wx_openid = $wxOpenid;
        $unionOpenidModel->updated_at = time();
        if ($unionOpenidModel->save()) {
            return ToolsHelper::funcReturn("绑定成功", true);
        }
        return ToolsHelper::funcReturn("绑定失败，请重试或联系微信客服");
    }

    /**
     * 是否关注了公众号
     *
     * @param $wxOpenid
     *
     * @return bool
     *
     * @author     xudt
     * @date-time  2021/8/21 14:02
     */
    public function isSubscribe($wxOpenid)
    {
        $officialOpenid =  UnionOpenid::find()->select(['official_openid'])->where(['wx_openid' => $wxOpenid])->scalar();
        if(!empty($officialOpenid)){
            $officialAccountService = new OfficialAccountService();
            $officialAccountInfo = $officialAccountService->getUserInfo($officialOpenid);
            if(!empty($officialAccountInfo['subscribe'])){
                return 1;
            }
        }
        return 0;
    }

}