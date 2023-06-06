<?php
return [
    'adminEmail' => 'admin@example.com',

    // 审核原因
    'reasonList' => [
        1 => '没有实物图片',
        2 => '重复发布',
        3 => '存在广告嫌疑',
        4 => '物品信息不全',
        5 => '物品地址不正确',
        6 => '与其他帐号发布相似物品',
        7 => '不属于闲置物品',
        8 => '物品图片模糊',
        9 => '请关联公众号',
        10 => '价格或入手价填写有误',
    ],

    /**
     * 打卡审核不通过理由
     */
    'clockReasonList' => [
        1 => '请上传打卡地标志性照片',
        2 => '重复发布',
        3 => '存在抄袭嫌疑',
        4 => '内容不符合',
        5 => '打卡地址不正确',
    ],

    /**
     * 兼职信息状态
     */
    'jobStatus' => [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核不通过',
        3 => '删除',
    ],

    'jobReasonList' => [
        1 => '信息填写不全',
        2 => '薪资填写错误',
        3 => '图片上传不规范',
        4 => '手机号未认证',
        5 => '重复发布',
    ],
];