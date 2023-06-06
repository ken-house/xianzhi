<?php
/**
 * 微信支付服务类
 * https://pay.weixin.qq.com/wiki/doc/apiv3/open/pay/chapter2_8_2.shtml
 * https://github.com/wechatpay-apiv3/wechatpay-php
 *
 * @author   xudt<xudengtang@km.com>
 * @date-time: 2021/11/2 16:48
 */

namespace common\services;

use common\helpers\RedisHelper;
use WeChatPay\Builder;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Formatter;
use WeChatPay\Util\PemUtil;
use Yii;

class WechatPayService
{
    // 商户号
    const MCH_ID = "1603791498";
    // 可通过下面这个方法获取，证书不更新就不会变
    // $merchantCertificateSerial = PemUtil::parseCertificateSerialNo($merchantCertificateFilePath);
    const MCH_SERIAL = "6B1178423CB41FE8B95C4666D8DCD1C00DADB60B";

    const API_V3_KEY = "qhPYr7dCi2ZtUklRojbDG64Nvc5LTQ8W";
    // 商户证书
    private $merchantCertificateFilePath = 'file://' . __DIR__ . '/../config/certs/wechat_pay/apiclient_cert.pem';
    // 商户私钥
    private $merchantPrivateKeyFilePath = 'file://' . __DIR__ . '/../config/certs/wechat_pay/apiclient_key.pem';
    // 平台证书 通过执行下面的命令生成
    // ./bin/CertificateDownloader.php -k ${apiV3key} -m ${mchId} -f ${mchPrivateKeyFilePath} -s ${mchSerialNo} -o ${outputFilePath}
    private $platformCertificateFilePath = 'file://' . __DIR__ . '/../config/certs/wechat_pay/wechatpay_2E7F07E1B97105B53C7DEA1D8BD75337C8F896F5.pem';

    private $instance;

    public function __construct()
    {
        // 加载商户私钥
        $merchantPrivateKeyInstance = Rsa::from($this->merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        // 加载「平台证书」公钥
        $platformPublicKeyInstance = Rsa::from($this->platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        // 解析「平台证书」序列号，「平台证书」当前五年一换，缓存后就是个常量
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($this->platformCertificateFilePath);
        // 工厂方法构造一个实例
        $this->instance = Builder::factory(
            [
                'mchid' => self::MCH_ID,
                'serial' => self::MCH_SERIAL,
                'privateKey' => $merchantPrivateKeyInstance,
                'certs' => [
                    $platformCertificateSerial => $platformPublicKeyInstance,
                ],
            ]
        );
    }

    /**
     * JSAPI下单
     *
     * @param array $params
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 11:22
     */
    public function orderPay($params = [])
    {
        $jsonBody = [
            'mchid' => self::MCH_ID,
            'out_trade_no' => $params['order_no'],
            'appid' => Yii::$app->params['weChat']['appid'],
            'description' => $params['desc'],
            'notify_url' => Yii::$app->params['domain'] . "/api/pay/notify",
            'attach' => json_encode($params['attach'], JSON_UNESCAPED_UNICODE),
            'amount' => [
                'total' => $params['price'],
                'currency' => 'CNY'
            ],
            'payer' => [
                'openid' => $params['openid'],
            ],
        ];
        // 设置订单过期时间 30分钟
        if (!empty($params['time_expire'])) {
            $jsonBody['time_expire'] = $params['time_expire'];
        }
        try {
            $resp = $this->instance
                ->v3->pay->transactions->jsapi
                ->post(
                    [
                        'json' => $jsonBody
                    ]
                );
            $statusCode = $resp->getStatusCode();
            if ($statusCode == 200) {
                $dataJson = $resp->getBody();
                $dataArr = json_decode($dataJson, true);

                // 保存prepay_id到redis
                $this->savePrePayIdToRedis($params['order_no'], $dataArr['prepay_id']);

                return $this->getWechatPayment($dataArr['prepay_id']);
            }
            return [];
        } catch (\Exception $e) {
            // 进行错误处理
            Yii::info(['func_name' => 'WechatPayService.orderPay', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 保存prepayId到redis，过期30分钟
     *
     * @param $orderNo
     * @param $prepayId
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/11/25 10:03
     */
    public function savePrePayIdToRedis($orderNo, $prepayId)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('orderPrepayId', $orderNo);
        return $redisBaseCluster->set($redisKey, $prepayId, 1800);
    }

    /**
     * 从redis获取$prepayId，为继续支付使用
     *
     * @param $orderNo
     *
     * @return false|string
     *
     * @author     xudt
     * @date-time  2021/11/25 10:00
     */
    public function getPrepayIdFromRedis($orderNo)
    {
        /** @var \redisCluster $redisBaseCluster */
        $redisBaseCluster = Yii::$app->get('redisBase')->getRedisCluster();
        $redisKey = RedisHelper::RK('orderPrepayId', $orderNo);
        $prepayId = $redisBaseCluster->get($redisKey);
        if (empty($prepayId)) {
            return "";
        }
        return $prepayId;
    }

    /**
     * 小程序调起支付参数，签名加密串相当于执行下面命令
     * echo -n -e \
     * "wx8888888888888888\n1414561699\n5K8264ILTKCH16CQ2502SI8ZNMTM67VS\nprepay_id=wx201410272009395522657a690389285100\n" \
     * | openssl dgst -sha256 -sign apiclient_key.pem \
     * | openssl base64 -A
     *
     * @param $prepayId
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 11:16
     */
    public function getWechatPayment($prepayId)
    {
        $merchantPrivateKeyInstance = Rsa::from($this->merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        $params = [
            'appId' => Yii::$app->params['weChat']['appid'],
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr' => Formatter::nonce(),
            'package' => 'prepay_id=' . $prepayId,
        ];
        $params += [
            'paySign' => Rsa::sign(
                Formatter::joinedByLineFeed(...array_values($params)),
                $merchantPrivateKeyInstance
            ),
            'signType' => 'RSA'
        ];
        return $params;
    }

    /**
     * 查询订单
     *
     * @param $orderNo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 09:55
     */
    public function queryOrder($orderNo)
    {
        try {
            return $this->instance
                ->v3->pay->transactions->outTradeNo->{'{out_trade_no}'}
                ->getAsync(
                    [
                        // 查询参数结构
                        'query' => ['mchid' => self::MCH_ID],
                        // uri_template 字面量参数
                        'out_trade_no' => $orderNo,
                    ]
                )
                ->then(
                    static function ($response) {
                        // 正常逻辑回调处理
                        $dataJson = $response->getBody();
                        return json_decode($dataJson, true);
                    }
                )
                ->otherwise(
                    static function ($e) {
                        // 进行错误处理
                        Yii::info(['func_name' => 'WechatPayService.queryOrder', 'message' => $e->getMessage()], 'trace');
                        return [];
                    }
                )
                ->wait();
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.queryOrder', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 关闭订单
     *
     * @param $orderNo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 14:36
     */
    public function closeOrder($orderNo)
    {
        try {
            return $this->instance
                ->v3->pay->transactions->outTradeNo->{'{out_trade_no}'}->close
                ->postAsync(
                    [
                        // 请求参数结构
                        'json' => ['mchid' => self::MCH_ID],
                        // uri_template 字面量参数
                        'out_trade_no' => $orderNo,
                    ]
                )
                ->then(
                    static function ($response) {
                        // 正常逻辑回调处理
                        $dataJson = $response->getBody();
                        return json_decode($dataJson, true);
                    }
                )
                ->otherwise(
                    static function ($e) {
                        // 进行错误处理
                        Yii::info(['func_name' => 'WechatPayService.closeOrder', 'message' => $e->getMessage()], 'trace');
                        return [];
                    }
                )
                ->wait();
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.closeOrder', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 订单回调通知
     *
     * @param $headers
     * @param $inBody
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 15:41
     */
    public function notify($headers, $inBody)
    {
        try {
            $inWechatpaySignature = $headers->get('Wechatpay-Signature');
            $inWechatpayTimestamp = $headers->get('Wechatpay-Timestamp');
//            $inWechatpaySerial = $headers->get('Wechatpay-Serial');
            $inWechatpayNonce = $headers->get('Wechatpay-Nonce');

            // 根据通知的平台证书序列号，查询本地平台证书文件，
            $platformPublicKeyInstance = Rsa::from($this->platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

            // 检查通知时间偏移量，允许5分钟之内的偏移
            $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
            $verifiedStatus = Rsa::verify(
            // 构造验签名串
                Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
                $inWechatpaySignature,
                $platformPublicKeyInstance
            );
            if ($timeOffsetStatus && $verifiedStatus) {
                // 转换通知的JSON文本消息为PHP Array数组
                $inBodyArray = (array)json_decode($inBody, true);
                // 使用PHP7的数据解构语法，从Array中解构并赋值变量
                [
                    'resource' => [
                        'ciphertext' => $ciphertext,
                        'nonce' => $nonce,
                        'associated_data' => $aad
                    ]
                ] = $inBodyArray;
                // 加密文本消息解密
                $inBodyResource = AesGcm::decrypt($ciphertext, self::API_V3_KEY, $nonce, $aad);
                // 把解密后的文本转换为PHP Array数组
                return (array)json_decode($inBodyResource, true);
            }
            return [];
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.notify', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 申请退款
     *
     * @param $params
     *
     * @return array|mixed
     *
     * @author     xudt
     * @date-time  2021/11/3 16:13
     */
    public function refundOrder($params)
    {
        try {
            return $this->instance
                ->chain('v3/refund/domestic/refunds')
                ->postAsync(
                    [
                        'json' => [
                            'out_trade_no' => $params['out_trade_no'],
                            'out_refund_no' => $params['refund_no'],
                            'reason' => $params['reason'],
                            'notify_url' => Yii::$app->params['domain'] ."/api/pay/refund-notify",
                            'amount' => [
                                'refund' => $params['refund_price'],
                                'total' => $params['order_amount'],
                                'currency' => 'CNY',
                            ],
                        ],
                    ]
                )
                ->then(
                    static function ($response) {
                        // 正常逻辑回调处理
                        $dataJson = $response->getBody();
                        return json_decode($dataJson, true);
                    }
                )
                ->otherwise(
                    static function ($e) {
                        // 进行错误处理
                        Yii::info(['func_name' => 'WechatPayService.refundOrder', 'message' => $e->getMessage()], 'trace');
                        return [];
                    }
                )
                ->wait();
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.refundOrder', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


    /**
     * 查询退款
     *
     * @param $refundNo
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 16:31
     */
    public function queryRefund($refundNo)
    {
        try {
            return $this->instance
                ->v3->refund->domestic->refunds->{'{out_refund_no}'}
                ->getAsync(
                    [
                        // uri_template 字面量参数
                        'out_refund_no' => $refundNo,
                    ]
                )
                ->then(
                    static function ($response) {
                        // 正常逻辑回调处理
                        $dataJson = $response->getBody();
                        return json_decode($dataJson, true);
                    }
                )
                ->otherwise(
                    static function ($e) {
                        // 进行错误处理
                        Yii::info(['func_name' => 'WechatPayService.queryRefund', 'message' => $e->getMessage()], 'trace');
                        return [];
                    }
                )
                ->wait();
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.queryRefund', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }

    /**
     * 交易订单列表（按天查询，不含当天，只能查最近三个月）
     *
     * @param $params ['bill_date'=>'2021-11-01']
     *
     * @return array
     *
     * @author     xudt
     * @date-time  2021/11/3 17:12
     */
    public function tradebill($params)
    {
        try {
            return $this->instance
                ->v3->bill->tradebill
                ->getAsync(
                    [
                        // 查询参数结构
                        'query' => $params,
                    ]
                )
                ->then(
                    static function ($response) {
                        // 正常逻辑回调处理
                        $dataJson = $response->getBody();
                        return json_decode($dataJson, true);
                    }
                )
                ->otherwise(
                    static function ($e) {
                        // 进行错误处理
                        Yii::info(['func_name' => 'WechatPayService.tradebill', 'message' => $e->getMessage()], 'trace');
                        return [];
                    }
                )
                ->wait();
        } catch (\Exception $e) {
            Yii::info(['func_name' => 'WechatPayService.tradebill', 'message' => $e->getMessage()], 'trace');
            return [];
        }
    }


}