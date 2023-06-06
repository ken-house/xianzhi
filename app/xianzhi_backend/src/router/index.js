import Vue from 'vue'
import Router from 'vue-router'
import ProductList from '@/views/ProductList'
import ClockList from '@/views/ClockList'
import JobList from '@/views/JobList'
import RewardPoint from '@/views/RewardPoint'
import GroupBuyProductList from '@/views/group_buy/product/Index'
import GroupBuyProductEdit from '@/views/group_buy/product/Edit'
import GroupBuyShopList from '@/views/group_buy/shop/Index'
import GroupBuyShopEdit from '@/views/group_buy/shop/Edit'
import GroupBuyShopManagerList from '@/views/group_buy/shop_manager/Index'
import GroupBuyShopManagerEdit from '@/views/group_buy/shop_manager/Edit'
import GroupBuyDspList from '@/views/group_buy/dsp/Index'
import GroupBuyOrderList from '@/views/group_buy/order/Index'
import GroupBuyWithdrawList from '@/views/group_buy/withdraw/Index'
import GroupBuyProductOptionList from '@/views/group_buy/product_option/Index'
import GroupBuyProductOptionEdit from '@/views/group_buy/product_option/Edit'
import Login from '@/views/Login'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/login',
            name: '登录',
            meta: {
                title: '登录',
                pathName: '登录'
            },
            component: Login,
        },
        {
            path: '/product-audit',
            name: '商品审核',
            meta: {
                title: '商品审核',
                pathName: '商品审核'
            },
            component: ProductList,
        },
        {
            path: '/clock-audit',
            name: '打卡审核',
            meta: {
                title: '打卡审核',
                pathName: '打卡审核'
            },
            component: ClockList,
        },
        {
            path: '/job-audit',
            name: '兼职审核',
            meta: {
                title: '兼职审核',
                pathName: '兼职审核'
            },
            component: JobList,
        },
        {
            path: '/reward-point',
            name: '积分奖励',
            meta: {
                title: '积分奖励',
                pathName: '积分奖励'
            },
            component: RewardPoint,
        },
        {
            path: '/groupBuy-productList',
            name: '团购活动列表',
            meta: {
                title: '团购活动列表',
                pathName: '团购活动列表'
            },
            component: GroupBuyProductList,
        },
        {
            path: '/groupBuy-productEdit',
            name: '团购活动编辑',
            meta: {
                title: '团购活动编辑',
                pathName: '团购活动编辑'
            },
            component: GroupBuyProductEdit,
        },
        {
            path: '/groupBuy-shopList',
            name: '团购店铺列表',
            meta: {
                title: '团购店铺列表',
                pathName: '团购店铺列表'
            },
            component: GroupBuyShopList,
        },
        {
            path: '/groupBuy-shopEdit',
            name: '团购店铺编辑',
            meta: {
                title: '团购店铺编辑',
                pathName: '团购店铺编辑'
            },
            component: GroupBuyShopEdit,
        },
        {
            path: '/groupBuy-managerList',
            name: '团购店铺管理员列表',
            meta: {
                title: '团购店铺管理员列表',
                pathName: '团购店铺管理员列表'
            },
            component: GroupBuyShopManagerList,
        },
        {
            path: '/groupBuy-managerEdit',
            name: '团购店铺管理员编辑',
            meta: {
                title: '团购店铺管理员编辑',
                pathName: '团购店铺管理员编辑'
            },
            component: GroupBuyShopManagerEdit,
        },
        {
            path: '/groupBuy-dspList',
            name: '团购分销店主列表',
            meta: {
                title: '团购分销店主列表',
                pathName: '团购分销店主列表'
            },
            component: GroupBuyDspList,
        },
        {
            path: '/groupBuy-orderList',
            name: '团购订单列表',
            meta: {
                title: '团购订单列表',
                pathName: '团购订单列表'
            },
            component: GroupBuyOrderList,
        },
        {
            path: '/groupBuy-withdrawList',
            name: '团购提现列表',
            meta: {
                title: '团购提现列表',
                pathName: '团购提现列表'
            },
            component: GroupBuyWithdrawList,
        },
        {
            path: '/groupBuy-productOptionList',
            name: '团购购买选项列表',
            meta: {
                title: '团购购买选项列表',
                pathName: '团购购买选项列表'
            },
            component: GroupBuyProductOptionList,
        },
        {
            path: '/groupBuy-productOptionEdit',
            name: '团购购买选项编辑',
            meta: {
                title: '团购购买选项编辑',
                pathName: '团购购买选项编辑'
            },
            component: GroupBuyProductOptionEdit,
        }
    ]
})
