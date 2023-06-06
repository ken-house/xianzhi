import { post, get } from '@/http/request';

// 登录
export function api_login(reqData) {
    return post('/api/login/login', reqData);
}

// 登出
export function api_logout(reqData) {
    return post('/api/login/logout', reqData);
}