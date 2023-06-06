import { post, get } from '@/http/request';

// 用户管理-用户列表
export function api_content_management_user_list(reqData) {
    return get('/api/user/list', reqData);
}