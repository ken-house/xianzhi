import { post, get } from '@/http/request';

// 团购管理-团购活动列表
export function api_content_management_groupbuy_product_list(reqData) {
    return get('/api/group-buy/product-list', reqData);
}

// 团购管理-团购活动详情
export function api_content_management_groupbuy_product_info(reqData) {
    return get('/api/group-buy/product-info', reqData);
}

// 团购管理-团购活动状态修改
export function api_content_management_groupbuy_product_status(reqData) {
    return post('/api/group-buy/product-status', reqData);
}

// 团购管理-团购活动保存
export function api_content_management_groupbuy_product_save(reqData) {
    return post('/api/group-buy/product-save', reqData);
}

// 团购管理-团购商家列表
export function api_content_management_groupbuy_shop_list(reqData) {
    return get('/api/group-buy/shop-list', reqData);
}

// 团购管理-团购商家详情
export function api_content_management_groupbuy_shop_info(reqData) {
    return get('/api/group-buy/shop-info', reqData);
}

// 团购管理-团购商家状态修改
export function api_content_management_groupbuy_shop_status(reqData) {
    return post('/api/group-buy/shop-status', reqData);
}

// 团购管理-团购活动保存
export function api_content_management_groupbuy_shop_save(reqData) {
    return post('/api/group-buy/shop-save', reqData);
}

// 团购管理-团购商家管理员列表
export function api_content_management_groupbuy_shop_manager_list(reqData) {
    return get('/api/group-buy/manager-list', reqData);
}

// 团购管理-团购商家管理员状态变更
export function api_content_management_groupbuy_shop_manager_status(reqData) {
    return post('/api/group-buy/manager-status', reqData);
}

// 团购管理-团购商家管理员修改
export function api_content_management_groupbuy_shop_manager_add(reqData) {
    return post('/api/group-buy/manager-add', reqData);
}

// 团购管理-团购分销店主列表
export function api_content_management_groupbuy_dsp_list(reqData) {
    return get('/api/group-buy/dsp-list', reqData);
}

// 团购管理-团购分销店主状态修改
export function api_content_management_groupbuy_dsp_status(reqData) {
    return post('/api/group-buy/dsp-status', reqData);
}

// 团购管理-团购订单列表
export function api_content_management_groupbuy_order_list(reqData) {
    return get('/api/group-buy/order-list', reqData);
}

// 团购管理-团购订单列表
export function api_content_management_groupbuy_order_refund(reqData) {
    return post('/api/group-buy/order-refund', reqData);
}

// 团购管理-团购提现列表
export function api_content_management_groupbuy_withdraw_list(reqData) {
    return get('/api/group-buy/withdraw-list', reqData);
}

// 团购管理-团购提现状态修改
export function api_content_management_groupbuy_withdraw_status(reqData) {
    return post('/api/group-buy/withdraw-status', reqData);
}

// 团购管理-团购商品购买选项列表
export function api_content_management_groupbuy_product_option_list(reqData) {
    return get('/api/group-buy/product-option-list', reqData);
}

// 团购管理-团购商品购买选项详情
export function api_content_management_groupbuy_product_option_info(reqData) {
    return get('/api/group-buy/product-option-info', reqData);
}


// 团购管理-团购商品购买选项状态修改
export function api_content_management_groupbuy_product_option_status(reqData) {
    return post('/api/group-buy/product-option-status', reqData);
}

// 团购管理-团购商品购买选项编辑
export function api_content_management_groupbuy_product_option_save(reqData) {
    return post('/api/group-buy/product-option-save', reqData);
}









