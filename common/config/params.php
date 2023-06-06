<?php

return [
    //微信小程序
    "weChat" => [
        'appid' => 'wxe1ac8e07ccb42255',
        'secret' => 'b564e13fda6b47c7a7d75958160d5bb2'
    ],

    // 公众号
    'officialAccount' => [
        'appid' => 'wx2c2c44762e2fa049',
        'secret' => 'd3edf1ee7f8b8e040a229b9621dca2df'
    ],

    // 阿里云短信
    "alibabaSms" => [
        'accessKey' => 'LTAI4GAMucYN78TeQFvRytuK',
        'accessSecret' => 'A0xGKqFiuQQGUuQJnXTvSqTY9HosgB',
    ],

    /**
     * 当前审核版本号
     */
    'auditVersionNum' => 50100,

    /**
     * 未登录时用户默认数据
     */
    'noLoginUser' => [
        'uid' => 0,
        'nickname' => '',
        'gender' => 0,
        'avatar' => '',
        'invite_code' => '',
        'reward_point' => 0,
        'created_at' => 0,
    ],

    /**
     * 商品标签
     */
    'productTags' => [
        "自提",
        "周边送货上门",
        "全新未拆封",
        "九成新",
        "可小刀",
        "一口价",
        "保证正品",
        "现场验货",
        "不退不换",
    ],

    /**
     * 商品状态
     */
    'productStatus' => [
        0 => '待审核',
        1 => '上架',
        2 => '不通过',
        3 => '下架',
        4 => '已卖出',
        5 => '删除',
    ],


    /**
     * 距离
     */
    'distType' => [
        0 => [
            'title' => '综合推荐',
            'short_title' => '全部',
        ],
        2 => [
            'title' => '附近2km',
            'short_title' => '2km',
        ],
        5 => [
            'title' => '附近5km',
            'short_title' => '5km',
        ],
        10 => [
            'title' => '附近10km',
            'short_title' => '10km',
        ],
        50 => [
            'title' => '附近50km',
            'short_title' => '50km',
        ],
    ],

    /**
     * 默认头像
     */
    'defaultAvatar' => '',

    /**
     * 消息类型
     */
    'messageType' => [
        0 => '私信消息',
        //以下属于系统消息
        1 => '点赞通知',
        2 => '评论通知',
        3 => '回复通知',
        4 => '审核通知',
        5 => '审核通知',
        6 => '积分兑换通知',
        7 => '邀请好友通知',
        8 => '强制下架通知',
        9 => '积分奖励通知',
        10 => '打卡审核通知',
        11 => '打卡审核通知',
        12 => '打卡强制下架通知',
        13 => '打卡浏览数奖励通知',
        14 => '订单返利到账通知',
        15 => '订单返利申请成功通知',
        16 => '积分过期通知',
        17 => '兼职审核通知',
        18 => '兼职审核通知',
        19 => '兼职强制下架通知',
    ],

    /**
     * 积分类型
     * exp 1 加 -1 减
     * show_task 1 是否在任务中心展示 0 不展示
     * type 0 普通任务  1 一次性任务 2 每日任务
     */
    'awardType' => [
        // ****************每日任务*****************
        1 => [
            'title' => '每日浏览',
            'exp' => 1,
            'point' => 10,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/view.png',
            'sort' => 5,
            'show_task' => 1,
            'type' => 2,
        ],
        2 => [
            'title' => '每日点赞',
            'point' => 10,
            'exp' => 1,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/thumb.png',
            'sort' => 6,
            'show_task' => 1,
            'type' => 2,
        ],
        3 => [
            'title' => '每日评论',
            'point' => 10,
            'exp' => 1,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/comment.png',
            'sort' => 7,
            'show_task' => 1,
            'type' => 2,
        ],
        4 => [
            'title' => '每日想要',
            'point' => 10,
            'exp' => 1,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/want.png',
            'sort' => 8,
            'show_task' => 1,
            'type' => 2,
        ],
        5 => [
            'title' => '每日分享',
            'point' => 100,
            'exp' => 1,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/share.png',
            'sort' => 3,
            'show_task' => 1,
            'type' => 2,
        ],
//        6 => [
//            'title' => '每日看小视频',
//            'point' => 200,
//            'exp' => 1,
//            'info' => '完播小视频赚%s积分',
//            'path' => '',
//            'img_url' => '/images/task/video.png',
//            'sort' => 3,
//            'show_task' => 1,
//            'type' => 2,
//        ],
//        7 => [
//            'title' => '每日答题',
//            'point' => 100,
//            'exp' => 1,
//            'info' => '答题正确赚%s积分',
//            'path' => '/pages/question/question',
//            'img_url' => '/images/task/question.png',
//            'sort' => 3,
//            'show_task' => 1,
//            'type' => 2,
//        ],
//        8 => [
//            'title' => '签到翻倍',
//            'point' => 0,
//            'exp' => 1,
//            'info' => '签到成功后看视频积分翻倍',
//            'path' => '',
//            'img_url' => '/images/task/sign.png',
//            'sort' => 2,
//            'show_task' => 1,
//            'type' => 2,
//        ],
        16 => [
            'title' => '每日搜索',
            'point' => 10,
            'exp' => 1,
            'info' => '赚%s积分',
            'path' => '/pages/index/index',
            'img_url' => '/images/task/search.png',
            'sort' => 3,
            'show_task' => 1,
            'type' => 2,
        ],
        62 => [
            'title' => '每日签到',
            'point' => 0,
            'exp' => 1,
            'show_task' => 0,
            'type' => 2,
        ],
        82 => [
            'title' => '每日浏览电商60秒',
            'point' => 50,
            'exp' => 1,
            'info' => '电商页面浏览60秒赚%s积分',
            'path' => '/pages/business_product/business_product?source_id=1',
            'img_url' => '/images/task/timecount.png',
            'sort' => 3,
            'show_task' => 1,
            'type' => 2,
        ],
        84 => [
            'title' => '每日一单',
            'point' => 1000,
            'exp' => 1,
            'info' => '每天一笔购物订单赚%s积分',
            'path' => '/pages/business_product/business_product?source_id=2',
            'img_url' => '/images/task/dayshop.png',
            'sort' => 3,
            'show_task' => 1,
            'type' => 2,
        ],


        // ********************一次性任务*******************
//        9 => [
//            'title' => '绑定微信号',
//            'point' => 1000,
//            'exp' => 1,
//            'info' => '绑定并同意复制赚%s积分',
//            'path' => '/pages/wechat/wechat',
//            'img_url' => '/images/task/bindwx.png',
//            'sort' => 2,
//            'show_task' => 1,
//            'type' => 1,
//        ],
//        10 => [
//            'title' => '微信加客服好友',
//            'point' => 1888,
//            'exp' => 1,
//            'info' => '加客服为好友进群聊赚%s积分',
//            'path' => '',
//            'img_url' => '/images/task/welfare.png',
//        ],
        15 => [
            'title' => '关联公众号',
            'point' => 1000,
            'exp' => 1,
            'info' => '关联公众号奖励%s积分',
            'path' => '/pages/gongzhonghao/gongzhonghao',
            'img_url' => '/images/task/gongzhonghao.png',
            'sort' => 1,
            'show_task' => 1,
            'type' => 1,
        ],
        83 => [
            'title' => '首次下单',
            'point' => 2000,
            'exp' => 1,
            'info' => '首笔订单申请返利奖励%s积分',
            'path' => '/pages/business_product/business_product?type=1',
            'img_url' => '/images/task/firstshop.png',
            'sort' => 1,
            'show_task' => 1,
            'type' => 1,
        ],


        // *********************普通任务**********************
        11 => [
            'title' => '好友邀请',
            'point' => 1000,
            'exp' => 1,
            'info' => '每邀请一位好友注册赚%s积分',
            'path' => '/pages/invite/invite',
            'img_url' => '/images/task/invite.png',
            'sort' => 11,
            'show_task' => 1,
            'type' => 0,
        ],
        12 => [
            'title' => '发布宝贝',
            'point' => 500,
            'exp' => 1,
            'info' => '每审核通过一件宝贝赚%s积分',
            'path' => '/pages/publish/publish',
            'img_url' => '/images/task/publish.png',
            'sort' => 12,
            'show_task' => 1,
            'type' => 0,
        ],
//        13 => [
//            'title' => '发布打卡',
//            'point' => 500,
//            'exp' => 1,
//            'info' => '每审核通过一次打卡赚%s积分',
//            'path' => '/pages/clock/publish/publish',
//            'img_url' => '/images/task/publish.png',
//            'sort' => 13,
//            'show_task' => 1,
//            'type' => 0,
//        ],
//        14 => [
//            'title' => '打卡浏览数奖励（最多5次）',
//            'point' => 100,
//            'exp' => 1,
//            'info' => '每100浏览数获得%s积分',
//            'path' => '/pages/clock/index/index',
//            'img_url' => '/images/task/view.png',
//            'sort' => 10,
//            'show_task' => 1,
//            'type' => 0,
//        ],


        // ***********************积分消耗类型***********************
        21 => [
            'title' => '擦亮宝贝',
            'point' => 30,
            'exp' => -1,
        ],
        22 => [
            'title' => '积分过期',
            'point' => 0, // 需要计算
            'exp' => -1,
        ],
        23 => [
            'title' => '想要免费商品',
            'point' => 100, // 写死，若想要修改需修改小程序的提示语
            'exp' => -1,
        ],


        // ***************************积分兑换类型*********************
        31 => [
            'title' => '兑换1元现金红包',
            'point' => 10000,
            'exp' => -1,
        ],
        32 => [
            'title' => '兑换5元现金红包',
            'point' => 50000,
            'exp' => -1,
        ],
        33 => [
            'title' => '兑换10元现金红包',
            'point' => 100000,
            'exp' => -1,
        ],
        41 => [
            'title' => '兑换10元手机充值卡',
            'point' => 100000,
            'exp' => -1,
        ],
        42 => [
            'title' => '兑换20元手机充值卡',
            'point' => 200000,
            'exp' => -1,
        ],
        51 => [
            'title' => '兑换优酷视频1个月vip会员',
            'point' => 200000,
            'exp' => -1,
        ],
        52 => [
            'title' => '兑换腾讯视频1个月vip会员',
            'point' => 300000,
            'exp' => -1,
        ],
        53 => [
            'title' => '兑换爱奇艺视频1个月vip会员',
            'point' => 250000,
            'exp' => -1,
        ],


        // ****************************活动奖励**************************
        61 => [
            'title' => '大转盘抽奖',
            'point' => 0,
            'exp' => 1,
        ],
        63 => [
            'title' => '连续签到',
            'point' => 0,
            'exp' => 1,
        ],
        81 => [
            'title' => '订单返利',
            'point' => 0,
            'exp' => 1,
        ],
        85 => [
            'title' => '下单数奖励',
            'point' => 0,
            'exp' => 1,
        ],
//        103 => [
//            'title' => '排行榜奖励',
//            'point' => 0,
//            'exp' => 1,
//        ],


        // **********************客服调整积分********************
        101 => [
            'title' => '客服增加积分',
            'point' => 0,
            'exp' => 1,
        ],
        102 => [
            'title' => '客服扣除积分',
            'point' => 0,
            'exp' => -1,
        ],
    ],

    /**
     * 兑换奖品列表，调整point时需与awardType一起修改point
     * 用于展示可兑换奖品
     */
    'prizeArr' => [
        // 兑换现金
        [
            'id' => 1,
            'title' => '1元现金红包',
            'image_url' => '/images/prize/hongbao_1.png',
            'price' => 0,
            'point' => 10000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 31,
            'status' => 1,
            'sort' => 10,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，奖品发放通过微信红包的形式，请点击右侧"联系客服"。</p>
          <p class="rule-item">3、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],
        [
            'id' => 2,
            'title' => '5元现金红包',
            'image_url' => '/images/prize/hongbao_5.png',
            'price' => 0,
            'point' => 50000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 32,
            'status' => 1,
            'sort' => 11,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，奖品发放通过微信红包的形式，请点击右侧"联系客服"。</p>
          <p class="rule-item">3、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],
        [
            'id' => 3,
            'title' => '10元现金红包',
            'image_url' => '/images/prize/hongbao_10.png',
            'price' => 0,
            'point' => 100000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 33,
            'status' => 1,
            'sort' => 12,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，奖品发放通过微信红包的形式，请点击右侧"联系客服"。</p>
          <p class="rule-item">3、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],


        //兑换手机充值
        [
            'id' => 11,
            'title' => '10元手机充值',
            'image_url' => '/images/prize/chongzhi_10.png',
            'price' => 10.00,
            'point' => 100000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 41,
            'status' => 1,
            'sort' => 1,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、目前仅支持移动、电信，<span class="rule-tips">联通暂不支持。</span></p>
          <p class="rule-item">3、兑换此奖品（在线充值）必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，请点击右侧"联系客服"，提供需要充值的手机号。</p>
          <p class="rule-item">4、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],

        [
            'id' => 12,
            'title' => '20元手机充值',
            'image_url' => '/images/prize/chongzhi_20.png',
            'price' => 20.00,
            'point' => 200000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 42,
            'status' => 1,
            'sort' => 13,
            'info' => '<p class="rule-item"><p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、支持移动、电信、联通。</p>
          <p class="rule-item">3、兑换此奖品（在线充值）必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，请点击右侧"联系客服"，提供需要充值的手机号。</p>
          <p class="rule-item">4、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],


        // 兑换虚拟商品
        [
            'id' => 21,
            'title' => '优酷视频1个月vip会员，原价20元',
            'image_url' => '/images/prize/youku.jpg',
            'price' => 20.00,
            'point' => 200000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 51,
            'status' => 1,
            'sort' => 3,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，请点击右侧"联系客服"，提供对应平台账号。</p>
          <p class="rule-item">3、充值/激活成功后，会员权益会立即生效。如您的账号已有VIP会员，会员有效期自然延长。</p>
          <p class="rule-item">4、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],
        [
            'id' => 22,
            'title' => '腾讯视频1个月vip会员，原价30元',
            'image_url' => '/images/prize/tengxun.jpg',
            'price' => 30.00,
            'point' => 300000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 52,
            'status' => 1,
            'sort' => 4,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，请点击右侧"联系客服"，提供对应平台账号。</p>
          <p class="rule-item">3、充值/激活成功后，会员权益会立即生效。如您的账号已有VIP会员，会员有效期自然延长。</p>
          <p class="rule-item">4、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],
        [
            'id' => 23,
            'title' => '爱奇艺视频1个月vip会员，原价25元',
            'image_url' => '/images/prize/aiyiqi.jpg',
            'price' => 25.00,
            'point' => 250000,
            'day_limit' => 100,
            'time_limit' => 0,
            'task_id' => 53,
            'status' => 1,
            'sort' => 5,
            'info' => '<p class="rule-item">1、<span class="rule-tips">每人每天仅可兑换本奖品一次。</span></p>
          <p class="rule-item">2、兑换此奖品必须先<span class="rule-tips">绑定微信号，关联公众号，完成首单任务</span>，请点击右侧"联系客服"，提供对应平台账号。</p>
          <p class="rule-item">3、充值/激活成功后，会员权益会立即生效。如您的账号已有VIP会员，会员有效期自然延长。</p>
          <p class="rule-item">4、若出现“<span class="rule-tips">会员优惠</span>”字样，表示有会员优惠哦。</p>
          <p class="rule-item margin-top-20">温馨提示：奖品数量有限，先到先得，兑完为止，一旦兑换不支持退换。</p>'
        ],
    ],


    /**
     * 商品分类
     */
    'categoryArr' => [
        1 => '婴幼儿用品',
        2 => '儿童用品',
        3 => '电子产品',
        4 => '厨卫用品',
        5 => '家具家电',
        6 => '洗护化妆品',
        7 => '箱包',
        8 => '宠物用品',
        9 => '运动健身',
        10 => '食品',
        11 => '生活用品',
        12 => '汽车用品',
    ],

    /**
     * 转盘奖品
     */
    'wheelPrize' => [
        [
            'id' => 0,
            'point' => 38,
            'prize_name' => '38积分',
            'color' => '#FCFCFC',
            'percent' => [ // 48%
                'min' => 1,
                'max' => 4800,
            ]
        ],
        [
            'id' => 1,
            'point' => 288,
            'prize_name' => '288积分',
            'color' => '#fced8d',
            'percent' => [ // 2%
                'min' => 4800,
                'max' => 5000,
            ]
        ],
        [
            'id' => 2,
            'point' => 68,
            'prize_name' => '68积分',
            'color' => '#FCFCFC',
            'percent' => [ // 18%
                'min' => 5000,
                'max' => 6800,
            ]
        ],
        [
            'id' => 3,
            'point' => 188,
            'prize_name' => '188积分',
            'color' => '#fced8d',
            'percent' => [ // 2%
                'min' => 6800,
                'max' => 7000,
            ]
        ],
        [
            'id' => 4,
            'point' => 58,
            'prize_name' => '58积分',
            'color' => '#FCFCFC',
            'percent' => [ // 10%
                'min' => 7000,
                'max' => 8000,
            ]
        ],
        [
            'id' => 5,
            'point' => 888,
            'prize_name' => '888积分',
            'color' => '#fced8d',
            'percent' => [ // 2%
                'min' => 8000,
                'max' => 8200,
            ]
        ],
        [
            'id' => 6,
            'point' => 88,
            'prize_name' => '88积分',
            'color' => '#FCFCFC',
            'percent' => [ // 18%
                'min' => 8200,
                'max' => 10000,
            ]
        ],
        [
            'id' => 7,
            'point' => 100000,
            'prize_name' => '10万积分',
            'color' => '#fced8d',
            'percent' => [ // 万分之一
                'min' => 10000,
                'max' => 10000,
            ]
        ],
    ],

    /**
     * 订单数量奖励金额
     * 订单数 => 积分
     */
    'orderNumAwardArr' => [
        5 => 5000,
        10 => 20000,
        20 => 50000,
        50 => 200000,
        100 => 1000000,
    ],

    /**
     * 签到奖励
     * 连续签到天数 => 积分
     */
    'signPrizeArr' => [
        1 => 10,
        2 => 20,
        3 => 40,
        4 => 80,
        5 => 160,
        6 => 320,
        7 => 640,
    ],

    /**
     * 签到目标奖励
     * 连续签到天数 => 积分
     */
    'signTargetPrizeArr' => [
        10 => 888,
        20 => 1888,
        50 => 3888,
        100 => 5888,
        150 => 8888,
        200 => 18888,
        250 => 28888,
        300 => 38888,
    ],

    /**
     * 首页导航
     */
    'navList' => [
        [
            'title' => '赚积分',
            'style' => 'nav-jifen',
            'link_url' => '/pages/task/task',
            'sort' => 1,
        ],
        [
            'title' => '京东',
            'style' => 'nav-jd',
            'link_url' => '/pages/business_product/business_product?source_id=1',
            'sort' => 3,
        ],
        [
            'title' => '拼多多',
            'style' => 'nav-pdd',
            'link_url' => '/pages/business_product/business_product?source_id=2',
            'sort' => 4,
        ],
        [
            'title' => '大转盘',
            'style' => 'nav-dazhuanpan',
            'link_url' => '/pages/wheel/wheel',
            'sort' => 2,
        ],
        [
            'title' => '排行榜',
            'style' => 'nav-paihang',
            'link_url' => '/pages/rank/rank',
            'sort' => 3,
        ],
        [
            'title' => '热门专区',
            'style' => 'nav-hot',
            'link_url' => '/pages/index_product/index_product?type=1',
            'sort' => 6,
        ],
        [
            'title' => '免费专区',
            'style' => 'nav-low-price',
            'link_url' => '/pages/index_product/index_product?type=2',
            'sort' => 6,
        ],
        [
            'title' => '本地团购',
            'style' => 'nav-groupbuy',
            'link_url' => '/pages/group_buy/index/index',
            'sort' => 2,
        ],
        [
            'title' => '公众号',
            'style' => 'nav-gongzhonghao',
            'link_url' => '/pages/gongzhonghao/gongzhonghao',
            'sort' => 5,
        ],
        [
            'title' => '找兼职',
            'style' => 'nav-jianzhi',
            'link_url' => '/pages/parttime_job/index/index',
            'sort' => 5,
        ],
        [
            'title' => '宠物领养',
            'style' => 'nav-chongwu',
            'link_url' => '/pages/index_product/index_product?type=3',
            'sort' => 7,
        ],
        [
            'title' => '房产出租',
            'style' => 'nav-zufang',
            'link_url' => '/pages/index_product/index_product?type=4',
            'sort' => 8,
        ],
    ],

    /**
     * 用户等级
     * 该等级最小积分 => 等级
     */
    'userLevelArr' => [
        2000000 => 8,
        1000000 => 7,
        300000 => 6,
        200000 => 5,
        100000 => 4,
        50000 => 3,
        20000 => 2,
        10000 => 1,
        0 => 0,
    ],

    /**
     * 可置顶页面
     */
    'stickPageTypeArr' => [
        0 => '首页',
        1 => '热门页',
        2 => '特价页',
        3 => '活动页',
    ],

    // 商家用户uid
    'businessUidArr' => [
        '100010',
        '100112',
        '100132',
        '100134',
        '100334',
        '100685',

        // 错误绑定微信号
        '100997',
        '100332',
        '101012',
        '100294',
        '100761',
        '100815',
    ],

    /**
     * 会员特权
     */
    'vipPrivillegeArr' => [
        1 => [
            'id' => 1,
            'style' => 'no-ad',
            'title' => '免广告',
            'info' => '开屏除外',
            'min_level' => 5,
            'sort' => 2,
        ],
        2 => [
            'id' => 2,
            'style' => 'no-audit',
            'title' => '修改免审核',
            'info' => '不限次数',
            'min_level' => 2,
            'sort' => 3,
        ],
        3 => [
            'id' => 3,
            'style' => 'no-money',
            'title' => '免费商品',
            'info' => '无需积分',
            'min_level' => 1,
            'sort' => 4,
        ],
        4 => [
            'id' => 4,
            'style' => 'discount',
            'title' => '会员优惠',
            'info' => '%s折兑换',
            'min_level' => 5,
            'sort' => 1,
        ],
    ],

    /**
     * 会员折扣
     */
    'vipDiscountArr' => [
        7 => 90,
        6 => 95,
        5 => 98,
    ],

    /**
     * 打卡状态
     */
    'clockStatus' => [
        0 => '待审核',
        1 => '审核通过',
        2 => '不通过',
        3 => '删除',
    ],

    // 战队竞赛活动期数
    'teamData' => [
        'period_num' => 1,
        'rule' => [
            [
                'title' => '活动参与规则',
                'content' => '<ol>
                    <li>每位用户可以在活动期间加入任意一支战队，加入后不可更改；</li>
                    <li>每位用户也可以在活动期间创建一支战队，创建战队成功后，战队名称不可更改；</li>
                    <li>任意分享小程序页面（包括首页、宝贝详情页、战队活动页、战队列表页、战队详情页等）给微信好友，微信好友加入邀请者战队即完成一次邀请；</li>
                    <li>活动期间第一次登录的用户加入战队将产生1000积分，其他登录用户加入战队将产生100积分，积分将累加到奖池；</li>
                    <li>活动结束后，根据战队人数排名，冠军战队将获得抽奖机会；</li>
                    <li>如有疑问，请联系官方客服。活动最终解释权归平台所有；</li>
                </ol>',
            ],
            [
                'title' => '奖品抽奖规则',
                'content' => '<ol>
                    <li>每期活动结束后，系统会自动生成奖项；</li>
                    <li>冠军战队成员每人对每个奖项均可获得一次抽奖机会，抽奖不分先后顺序，先抽先得，请点击右侧"联系客服"，领取微信红包，兑换比例为10000积分=1元；</li>
                    <li>每位冠军战队成员每期活动仅能抽中一次奖品；</li>
                    <li>每位成员抽奖概率不同，根据成员邀请加入战队人数占战队总邀请人数比例计算得到的概率值，邀请人数越多，中奖概率越大；</li>
                    <li>抽奖截止时间为每期活动结束后两天内（具体可参考奖品列表上标注截止时间），逾期视为放弃抽奖；</li>
                    <li>若抽奖截止时间后，奖品仍未被领取，未领取的奖品积分将累加到下期活动的奖池；</li>
                    <li>如有疑问，请联系官方客服。活动最终解释权归平台所有；</li>
                </ol>',
            ],
        ]
    ],

    /**
     * 首页公告通知
     */
    'noticeSetting' => [
        'show' => 0,
        'version' => 9,
        'title' => '5.1.0版本新功能',
        'content' => '<p class="draw-content-p">1、新推出本地团购，价格超实惠，涵盖餐饮、休闲娱乐等行业，支持在线下单，商家入驻后团购商品会更多，普通用户可申请分销开店，期待您加入；</p>
                      <p class="draw-content-p">2、增加客服系统，可直接点击联系客服，联系在线客服；</p>'
    ],

    /**
     * 电商商品来源
     */
    'businessProductSource' => [
        1 => '京东',
        2 => '拼多多',
    ],

    /**
     * 电商精选频道
     */
    'chosenChannelTypeArr' => [
        1 => [
            [
                'channel_type' => 33,
                'channel_name' => '京东秒杀',
                'source_id' => 1,
            ],
            [
                'channel_type' => 10,
                'channel_name' => '9.9包邮',
                'source_id' => 1,
            ],
        ],
        2 => [
            [
                'channel_type' => 4,
                'channel_name' => '秒杀活动',
                'source_id' => 2,
            ],
            [
                'channel_type' => 7,
                'channel_name' => '百亿补贴',
                'source_id' => 2,
            ],
        ],
    ],

    /**
     * 京东订单状态
     */
    'businessProductOrderStatus' => [
        1 => [
            3 => '订单取消',
            15 => '待付款',
            16 => '已付款',
            17 => '确认收货',
            24 => '已付定金',
        ],
        2 => [
            0 => '已支付',
            1 => '已成团',
            2 => '确认收货',
            3 => '审核成功',
            4 => '订单取消',
            5 => '已结算',
            10 => '已处罚',
        ],
    ],

    /**
     * 兼职结算类型
     */
    'jobSettleTypeArr' => [
        0 => "面议",
        1 => "日结",
        2 => "月结",
        3 => "计件结",
    ],

    /**
     * 兼职菜单导航
     */
    'parttimeJobNavList' => [
        [
            'title' => '我的发布',
            'style' => 'nav-jianzhi-publish',
            'link_url' => '/pages/parttime_job/mycenter/mycenter',
            'sort' => 1,
        ]
    ],

    /**
     * 团购订单密钥
     */
    'groupBuyOrderSecret' => 'wHx3WavFUA8pNl9T',

    /**
     * 团购活动状态
     */
    'groupBuyProductStatus' => [
        0 => '待上架',
        1 => '上架',
        2 => '已下架',
    ],

    /**
     * 团购分销商开店状态
     */
    'groupBuyDspStatus' => [
        0 => '审核中',
        1 => '已开店',
        2 => '开店失败',
        3 => '已关店',
    ],

    /**
     * 团购商家状态
     */
    'groupBuyShopStatus' => [
        0 => '未合作',
        1 => '合作中',
        2 => '停止合作'
    ],

    /**
     * 团购商家管理员状态
     */
    'groupBuyShopManagerStatus' => [
        0 => '无效',
        1 => '有效',
    ],

    /**
     * 团购商品退款类型
     */
    'groupBuyRefundType' => [
        0 => '不支持退款',
        1 => '随时退',
        2 => '过期自动退款',
    ],

    /**
     * 团购订单状态
     */
    'groupBuyOrderStatus' => [
        0 => '待支付',
        1 => '支付成功',
        2 => '订单完成',
        3 => '退款审核中',
        4 => '退款完成',
        5 => '订单关闭',
        100 => '退款中',
        101 => '退款失败',
    ],

    /**
     * 团购提现状态
     */
    'groupBuyWithdrawStatus' => [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核不通过',
        3 => '已打款',
    ],

    /**
     * 团购菜单导航
     */
    'groupBuyNavList' => [
        [
            'title' => '收益概览',
            'style' => 'nav-groupbuy-overview',
            'link_url' => '/pages/group_buy/overview/overview',
            'sort' => 1,
        ],
        [
            'title' => '我的订单',
            'style' => 'nav-groupbuy-order',
            'link_url' => '/pages/group_buy/order/order',
            'sort' => 2,
        ],
        [
            'title' => '分销商品',
            'style' => 'nav-groupbuy-product',
            'link_url' => '/pages/group_buy/distribution/distribution',
            'sort' => 3,
        ],
        [
            'title' => '领券中心',
            'style' => 'nav-groupbuy-coupon',
            'link_url' => '/pages/group_buy/coupon/coupon',
            'sort' => 4,
        ],
    ],

    /**
     * 提现列表
     * 只能增加不可删除
     */
    'groupBuyWithdrawList' => [
        1 => [
            1 => [
                'id' => 1,
                'num' => 50,
                'status' => 1,
            ],
            2 => [
                'id' => 2,
                'num' => 100,
                'status' => 1,
            ],
            3 => [
                'id' => 3,
                'num' => 200,
                'status' => 1,
            ],
            4 => [
                'id' => 4,
                'num' => 300,
                'status' => 1,
            ],
            5 => [
                'id' => 5,
                'num' => 400,
                'status' => 1,
            ],
            6 => [
                'id' => 6,
                'num' => 500,
                'status' => 1,
            ],
        ],
        2 => [
            7 => [
                'id' => 7,
                'num' => 1000,
                'status' => 1,
            ],
            8 => [
                'id' => 8,
                'num' => 2000,
                'status' => 1,
            ],
            9 => [
                'id' => 9,
                'num' => 4000,
                'status' => 1,
            ],
            10 => [
                'id' => 10,
                'num' => 6000,
                'status' => 1,
            ],
            11 => [
                'id' => 11,
                'num' => 8000,
                'status' => 1,
            ],
            12 => [
                'id' => 12,
                'num' => 10000,
                'status' => 1,
            ],
        ]
    ],
];
