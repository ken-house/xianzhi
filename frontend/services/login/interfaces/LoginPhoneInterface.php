<?php
/**
 * 手机登录相关接口
 *
 * @author xudt
 * @date   : 2019/11/2 10:40
 */

namespace frontend\services\login\interfaces;

interface LoginPhoneInterface
{
    public function login($data = []);  //登录入口


}