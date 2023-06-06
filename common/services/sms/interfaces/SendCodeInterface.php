<?php
/**
 * @author xudt
 * @date   : 2019/11/2 16:33
 */

namespace common\services\sms\interfaces;

interface SendCodeInterface
{
    //发送验证码
    public function sendCode($phone, $type);

    //验证验证码
    public function verifyCode($phone, $type, $code);
}