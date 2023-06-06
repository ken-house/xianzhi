import { get, post, upload } from '@/http/request';

/**
 * @description 上传文件
 * @param {object} reqData 请求的数据包括：file、file_type、type、bath_add
 * @returns {Promise}
 */
 export function api_upload(reqData) {
    return upload('/api/upload/upload', reqData);
}