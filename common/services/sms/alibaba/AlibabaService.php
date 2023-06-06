<?php
/**
 * @author xudt
 * @date   : 2019/12/31 11:22
 */

namespace common\services\sms\alibaba;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use common\helpers\ToolsHelper;
use Yii;

class AlibabaService
{
    private $accessKey;
    private $accessSecret;

    public function __construct()
    {
        $alibabaSmsParams = Yii::$app->params['alibabaSms'];
        $this->accessKey = $alibabaSmsParams['accessKey'];
        $this->accessSecret = $alibabaSmsParams['accessSecret'];

        AlibabaCloud::accessKeyClient($this->accessKey, $this->accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
    }

    /**
     * 发送验证码
     * @author   xudt<xudengtang@km.com>
     * @dateTime 2020/9/30 16:20
     * @param $phone
     * @param $templateCode
     * @param $templateParam
     *
     * @return array
     */
    public function sendSms($phone, $templateCode, $templateParam)
    {
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => "小区闲置物品信息交流平台",
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => $templateParam,
                    ],
                ])
                ->request();
            $data = $result->toArray();
            if ($data['Code'] == "OK") {
                return ToolsHelper::funcReturn("验证码发送成功", true);
            } else {
                return ToolsHelper::funcReturn("验证码发送失败");
            }
        } catch (ClientException $e) {
            return ToolsHelper::funcReturn("异常", false, [
                'message' => $e->getErrorMessage() . PHP_EOL,
            ]);
        } catch (ServerException $e) {
            return ToolsHelper::funcReturn("异常", false, [
                'message' => $e->getErrorMessage() . PHP_EOL,
            ]);
        }
    }

    /**
     * 查询短信发送详情
     *
     * @param $phone
     * @param $sendDate
     * @param $currentPage
     *
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @author   xudt
     * @dateTime 2019/12/31 11:30
     */
    public function querySendDetails($phone, $sendDate, $currentPage)
    {
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('QuerySendDetails')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumber' => $phone,
                        'SendDate' => $sendDate,
                        'PageSize' => 50,
                        "CurrentPage" => $currentPage,
                    ],
                ])->request();
            $data = $result->toArray();
            if ($data['Code'] == "OK") {
                return ToolsHelper::funcReturn("短信列表", true, [
                    'list' => $data['SmsSendDetailDTOs']['SmsSendDetailDTO'],
                    'total' => $data['TotalCount']
                ]);
            } else {
                return ToolsHelper::funcReturn("获取失败");
            }
        } catch (ClientException $e) {
            return ToolsHelper::funcReturn("异常", false, [
                'message' => $e->getErrorMessage() . PHP_EOL,
            ]);
        } catch (ServerException $e) {
            return ToolsHelper::funcReturn("异常", false, [
                'message' => $e->getErrorMessage() . PHP_EOL,
            ]);
        }
    }
}