import { post, get } from '@/http/request';

// 兼职管理-打卡列表
export function api_content_management_job_list(reqData) {
    return get('/api/parttime-job/index', reqData);
}

// 兼职管理-审核通过
export function api_content_management_job_pass(reqData) {
    return post('/api/parttime-job/pass', reqData);
}

// 兼职管理-审核拒绝
export function api_content_management_job_refuse(reqData) {
    return post('/api/parttime-job/refuse', reqData);
}

// 兼职管理-强制下架
export function api_content_management_job_down(reqData) {
    return post('/api/parttime-job/down', reqData);
}