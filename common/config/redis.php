<?php

/**
 * rediskey定义
 *
 * @author xudt
 * @date   : 2019/11/7 17:41
 */
return [
    'redis.config' => [
        /*********************************************************base集群*******************************************************/
        /**
         * 类型hash
         * 功能：用户基础信息
         * 过期时间：15天
         * KEY参数：
         *    第一个参数为用户id
         */
        'userInfo' => [
            'key' => 'h:user_info:%s',
            'structure' => [
                'uid' => 'uid',
                'inviteCode' => 'invite_code', //我的邀请码
                'nickname' => 'nickname',
                'gender' => 'gender',
                'avatar' => 'avatar',
                'country' => 'country',
                'province' => 'province',
                'city' => 'city',
                'inviteUid' => 'invite_uid', //我的师傅
                'inviteAt' => 'invite_at', //绑定好友关系时间
                'inviteFriendNum' => 'invite_friend_num', //好友个数
                'status' => 'status',
                'lockedAt' => 'locked_at',
                'lockedReason' => 'locked_reason',
                'wxOpenid' => 'wx_openid',
                'password' => 'password',
                'phone' => 'phone',
                'wx' => 'wx',
                'wxPublic' => 'wx_public',
                'loginNum' => 'login_num',
                'latestLoginAt' => 'latest_login_at',
                'latestLoginIp' => 'latest_login_ip',
                'lastSignDay' => 'last_sign_day',
                'continueSignDay' => 'continue_sign_day',
                'createdAt' => 'created_at',
                'activeAt' => 'active_at',
                'rewardPoint' => 'reward_point',
            ]
        ],

        /**
         * 类型zset
         * 功能：记录用户登录生成的jwt时唯一值jti
         * 过期时间：30天(用户登录时会删除旧的redis)
         * KEY参数：
         *    第一个参数为用户id
         */
        'authTokenId' => [
            'key' => 'z:user_auth_tokenid_%s',
        ],

        /**
         * 类型zset
         * 功能：用户卖出、浏览、点赞、评论、想要商品
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为类型（sale、view、thumb、comment、want）
         *    第二个参数为uid
         */
        'userProductData' => [
            'key' => 'z:user_product_data:%s:%s',
        ],

        /**
         * 类型zset
         * 功能：用户推荐、不推荐
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为类型（tuijian,no_tuijian）
         *    第二个参数为uid
         */
        'userClockData' => [
            'key' => 'z:user_clock_data:%s:%s',
        ],

        /**
         * 类型geohash
         * 功能：查找附近的宝贝
         * 过期时间：永不过期
         *
         */
        'distGeo' => [
            'key' => 'g:dist_geo',
        ],

        /**
         * 类型geohash
         * 功能：查找附近的打卡
         * 过期时间：永不过期
         *
         */
        'distGeoClock' => [
            'key' => 'g:dist_geo_clock',
        ],

        /**
         * 类型geohash
         * 功能：查找附近的兼职
         * 过期时间：永不过期
         *
         */
        'distGeoJob' => [
            'key' => 'g:dist_geo_job',
        ],

        /**
         * 类型geohash
         * 功能：查找附近的团购
         * 过期时间：永不过期
         *
         */
        'distGeoGroupBuy' => [
            'key' => 'g:dist_geo_group_buy',
        ],

        /**
         * 类型geohash
         * 功能：显示附近的推广banner
         * 过期时间：永不过期
         *
         */
        'distGeoBanner' => [
            'key' => 'g:dist_geo_banner',
        ],

        /**
         * 类型geohash
         * 功能：显示附近的置顶
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为页面类型
         *    第二个参数为活动ID
         *
         */
        'distGeoStick' => [
            'key' => 'g:dist_geo_stick:%s:%s',
        ],

        /**
         * 类型 hash
         * 功能：商品点赞、评论、浏览、想要数据
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为商品ID
         */
        'productData' => [
            'key' => 'h:product_data:%s',
            'structure' => [
                'thumbNum' => 'thumb_num',
                'commentNum' => 'comment_num',
                'viewNum' => 'view_num',
                'wantNum' => 'want_num',
            ]
        ],

        /**
         * 类型 hash
         * 功能：商品浏览数据
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为商品ID
         */
        'clockData' => [
            'key' => 'h:clock_data:%s',
            'structure' => [
                'viewNum' => 'view_num',
                'tuijianNum' => 'tuijian_num',
                'noTuijianNum' => 'no_tuijian_num',
            ]
        ],

        /**
         * 类型 hash
         * 功能：兼职浏览数据
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为兼职ID
         */
        'jobData' => [
            'key' => 'h:job_data:%s',
            'structure' => [
                'viewNum' => 'view_num',
            ]
        ],

        /**
         * 类型 bitmap
         * 功能：商品点赞、评论、浏览、想要数据
         * 过期时间：三天后过期
         * KEY参数：
         *    第一个参数为typeId见配置
         *    第一个参数为日期，例如：20210402
         */
        'onceOfDateRewardPoint' => [
            'key' => 'b:once_of_date_reward_point:%s:%s',
        ],

        /**
         * 类型 bitmap
         * 功能：商品擦亮限制每天一次
         * 过期时间：三天后过期
         * KEY参数：
         *    第一个参数为日期，例如：20210402
         */
        'onceOfDateRefresh' => [
            'key' => 'b:once_of_date_refresh:%s',
        ],

        /**
         * 类型zset
         * 功能：记录兑换奖品个数
         * 过期时间：三天后过期
         * KEY参数：
         *    第一个参数为日期，例如：20210402
         */
        'prizeExchangeNum' => [
            'key' => 'z:prize_exchange_num:%s',
        ],

        /**
         * 类型 bitmap
         * 功能：用户是否兑换过
         * 过期时间：三天后过期
         * KEY参数：
         *    第一个参数为$prizeId
         *    第一个参数为日期，例如：20210402
         */
        'onceUserExchangePrize' => [
            'key' => 'b:once_user_exchange_prize:%s:%s',
        ],

        /**
         * 类型 zset
         * 功能：系统中获取积分和积分兑换
         * 过期时间：永不过期，保留100条-积分消费者处理
         */
        'userAwardPointRecord' => [
            'key' => 'z:user_award_point_record',
        ],

        /**
         * 类型zset
         * 功能：大转盘记录用户今日使用次数
         * 过期时间：三天
         * KEY参数：
         *    第一个参数为日期，例如：20210402
         */
        'wheelUserCount' => [
            'key' => 'z:wheel_user_count:%s',
        ],

        /**
         * 类型 bitmap
         * 功能：用户是否可以转大转盘
         * 过期时间：永不过期
         */
        'wheelUserRun' => [
            'key' => 'b:wheel_user_run',
        ],

        /**
         * 类型zset
         * 功能：用户积分排行榜
         * 过期时间：三天
         * KEY参数：
         *    第一个参数为日期，例如：20210402
         */
        'userPointRank' => [
            'key' => 'z:user_point_rank:%s',
        ],

        /**
         * 类型 zset
         * 功能：记录用户已答对题的id
         * 过期时间：永不过期
         */
        'userQuestion' => [
            'key' => 'z:user_question',
        ],

        /**
         * 类型 zset
         * 功能：记录每天用户想要的用户uid
         * 过期时间：三天
         * KEY参数：
         *    第一个参数为uid
         *    第二个参数为日期，例如：20210402
         */
        'userWantUid' => [
            'key' => 'z:user_want_uid:%s:%s',
        ],

        /**
         * 类型 set
         * 功能：活动要展示的商品ID
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为activity_id
         */
        'activityProduct' => [
            'key' => 'set:activity_product:%s'
        ],

        /**
         * 类型 set
         * 功能：未绑定微信用户id
         * 过期时间：永不过期
         */
        'noBindWxUserIdArr' => [
            'key' => 'set:no_bind_wx_user_id_arr',
        ],

        /**
         * 类型 set
         * 功能：免费商品扣除积分
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为uid
         */
        'wantFreeAward' => [
            'key' => 'set:want_free_award:%s',
        ],

        /**
         * 类型 bitmap
         * 功能：用户是否已抽奖
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为prizeId
         */
        'drawTeamPrize' => [
            'key' => 'b:draw_team_prize:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户喜好个性推荐
         * 过期时间：用不过期
         * KEY参数：
         *      第一个参数为uid
         */
        'userFavourite' => [
            'key' => 'z:user_favourite:%s',
        ],

        /**
         * 类型：hash
         * 功能：验证码
         * 过期时间：5分钟
         * KEY参数：
         *      第一个参数为类型  1  登录  2 绑定手机  3 换绑手机
         *      第一个参数为手机号
         */
        'phoneCode' => [
            'key' => 'h:phone_code:%s:%s',
            'structure' => [
                'verifyCode' => 'verify_code',  //验证码
                'tryCount' => 'try_count'       //尝试次数
            ]
        ],

        /**
         * 类型：string
         * 功能：下单记录订单的prepay_id，为继续支付提供prepay_id
         * 过期时间：30分钟
         * KEY参数：
         *      第一个参数为订单号
         */
        'orderPrepayId' => [
            'key' => 's:order_prepay_id:%s',
        ],


























        /************************************旧key************************************/
        /**
         * 类型：zset
         * 功能：用户新手任务
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为uid
         */
        'userOnceTask' => [
            'key' => 'z:user_once_task:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户日常任务
         * 过期时间：3天过期
         * KEY参数：
         *      第一个参数为uid
         *      第二个参数为date
         */
        'userNormalTask' => [
            'key' => 'z:user_normal_task:%s:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户成长任务
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为uid
         */
        'userGrowupTask' => [
            'key' => 'z:user_growup_task:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户人气任务
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为uid
         */
        'userPopularityTask' => [
            'key' => 'z:user_popularity_task:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户文章任务
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为article_key
         *
         */
        'userArticleTask' => [
            'key' => 'z:user_article_task:%s',
        ],

        /**
         * 类型：hash
         * 功能：用户当前位置
         * Key参数：
         *      第一个参数为uid
         */
        'userLocation' => [
            'key' => 'h:user_location:%s',
            'structure' => [
                'country' => 'country',
                'province' => 'province',
                'city' => 'city',
                'district' => 'district',
                'location' => 'location',
                'lat' => 'lat',
                'lng' => 'lng',
            ]
        ],

        /**
         * 类型：zset
         * 功能：用户到过的省份
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为uid
         *
         */
        'userProvince' => [
            'key' => 'z:user_province:%s',
        ],

        /**
         * 类型：zset
         * 功能：用户到过的城市
         * 过期时间：永不过期
         * KEY参数：
         *      第一个参数为uid
         *
         */
        'userCity' => [
            'key' => 'z:user_city:%s',
        ],

        /*********************************************************busy集群*******************************************************/

        /**
         * 类型：string
         * 功能：换绑验证原手机结果
         * 过期时间：5分钟
         * KEY参数：
         *      第一个参数为uid
         */
        'validateChangePhone' => [
            'key' => 's:validate_change_phone:%s',
        ],

        /**
         * 类型：zset
         * 功能：推荐热门搜索词
         * 过期时间：永不过期
         *
         */
        'hotSearchKeyword' => [
            'key' => 'z:hot_search_keyword',
        ],

        /**
         * 类型：zset
         * 功能：推荐热门搜索词
         * 过期时间：永不过期
         *
         */
        'focusKeyword' => [
            'key' => 'z:focus_keyword',
        ],

        /**
         * 类型：string
         * 功能：最受欢迎的用户列表
         * 过期时间：永不过期
         */
        'rankWelcomeUser' => [
            'key' => 's:rank_welcome_user',
        ],

        /**
         * 类型：string
         * 功能：去过城市最多的用户列表
         * 过期时间：永不过期
         */
        'rankTravelUser' => [
            'key' => 's:rank_travel_user',
        ],

        /**
         * 类型：string
         * 功能：金币最多的用户列表
         * 过期时间：永不过期
         */
        'rankHistoryCoinUser' => [
            'key' => 's:rank_history_coin_user',
        ],

        /**
         * 类型：string
         * 功能：昨日获取金币最多的用户列表
         * 过期时间：永不过期
         */
        'rankYesterdayCoinUser' => [
            'key' => 's:rank_yesterday_coin_user',
        ],

        /**
         * 类型：string
         * 功能：游记文章排行榜
         * 过期时间：永不过期
         */
        'rankHistoryArticle' => [
            'key' => 's:rank_history_article',
        ],

        /**
         * 类型：string
         * 功能：昨日点赞数最多的用户列表
         * 过期时间：永不过期
         */
        'rankYesterdayArticle' => [
            'key' => 's:rank_yesterday_article',
        ],

        /**
         * 类型：string
         * 功能：微信小程序access_token
         * 过期时间：7000s
         */
        'wechatAccessToken' => [
            'key' => 's:wechat_access_token',
        ],

        /**
         * 类型：string
         * 功能：公众号access_token
         * 过期时间：7000s
         */
        'officialAccountAccessToken' => [
            'key' => 's:official_account_access_token',
        ],

        /**
         * 类型：zset
         * 功能：修改用户敏感信息，对用户敏感信息进行审核
         * 过期时间：用不过期
         */
        'userAuditList' => [
            'key' => 'z:user_audit_list',
        ],

        /**
         * 类型：string
         * 功能：用户助力次数
         * 过期时间：一天
         * KEY参数：
         *      第一个参数为uid
         *      第一个参数为date
         */
        'userActivityHelp' => [
            'key' => 's:user_activity_help:%s:%s',
        ],

        /**
         * 类型：list
         * 功能：拼团红包
         * 过期时间：一周(活动区间暂时不删除)
         * KEY参数：
         *      第一个参数为group_key
         */
        'groupRedPacketActivity' => [
            'key' => 'l:group_red_packet_activity:%s',
        ],


        /*********************************************************business集群*******************************************************/

        /**
         * 类型zset
         * 功能：记录用户现金提现的订单状态
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为uid
         */
        'cashWithdrawOrderStatus' => [
            'key' => 'z:cash_withdraw_order_status:%s',
        ],

        /**
         * 类型zset
         * 功能：记录用户金币提现的订单状态
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为uid
         */
        'coinWithdrawOrderStatus' => [
            'key' => 'z:coin_withdraw_order_status:%s',
        ],

        /*********************************************************article集群****************************************************************/
        /**
         * 类型hash
         * 功能：记录文章的相关数据
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为articleKey
         */
        'articleData' => [
            'key' => 'h:article_data:%s',
            'structure' => [
                'uid' => 'uid',
                'thumbNum' => 'thumb_num',
                'score' => 'score',
//                'scoreManNum' => 'score_man_num',
                'viewNum' => 'view_num',
                'collectNum' => 'collect_num',
                'commentNum' => 'comment_num',
            ]
        ],

        /**
         * 类型bitmap
         * 功能：判断用户是否点赞过
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为articleKey
         */
        'articleThumb' => [
            'key' => 'b:article_thumb:%s',
        ],

        /**
         * 类型bitmap
         * 功能：判断用户是否点赞过评论
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为commentKey
         */
        'commentThumb' => [
            'key' => 'b:comment_thumb:%s',
        ],

        /**
         * 类型bitmap
         * 功能：判断用户是否点赞过回复
         * 过期时间：永不过期
         * KEY参数：
         *    第一个参数为replyKey
         */
        'replyThumb' => [
            'key' => 'b:reply_thumb:%s',
        ],

    ]
];