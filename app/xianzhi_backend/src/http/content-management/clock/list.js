import { post, get } from '@/http/request';

// 打卡管理-打卡列表
export function api_content_management_clock_list(reqData) {
    return get('/api/clock-audit/index', reqData);
}

// 打卡管理-审核通过
export function api_content_management_clock_pass(reqData) {
    return post('/api/clock-audit/pass', reqData);
}

// 打卡管理-审核拒绝
export function api_content_management_clock_refuse(reqData) {
    return post('/api/clock-audit/refuse', reqData);
}

// 打卡管理-强制下架
export function api_content_management_clock_down(reqData) {
    return post('/api/clock-audit/down', reqData);
}