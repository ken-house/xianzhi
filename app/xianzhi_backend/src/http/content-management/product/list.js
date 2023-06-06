import { post, get } from '@/http/request';

// 商品管理-文章列表
export function api_content_management_product_list(reqData) {
    return get('/api/product-audit/index', reqData);
}

// 商品管理-审核通过
export function api_content_management_product_pass(reqData) {
    return post('/api/product-audit/pass', reqData);
}

// 商品管理-审核拒绝
export function api_content_management_product_refuse(reqData) {
    return post('/api/product-audit/refuse', reqData);
}

// 商品管理-强制下架
export function api_content_management_product_down(reqData) {
    return post('/api/product-audit/down', reqData);
}

// 商品管理-修改分类
export function api_content_management_product_category(reqData) {
    return post('/api/product-audit/category', reqData);
}

// 商品管理-加入活动
export function api_content_management_product_activity(reqData) {
    return post('/api/product-audit/activity', reqData);
}

// 商品管理-置顶
export function api_content_management_product_stick(reqData) {
    return post('/api/product-audit/stick', reqData);
}

// 商品管理 - 商品分类
export function api_content_management_product_category_list(reqData){
    return get('/api/product-audit/category-list', reqData)
}