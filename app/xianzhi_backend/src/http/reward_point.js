import { post, get } from '@/http/request';

// 客服增减积分
export function api_reward_point(reqData) {
    return post('/api/reward-point/reward-point', reqData);
}