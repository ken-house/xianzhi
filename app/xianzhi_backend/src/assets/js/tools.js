export function deep_copy(obj) { //  深拷贝
    return JSON.parse(JSON.stringify(obj));
}

export function filter_empty_params(data, validationField) { //  过滤空属性   data为数据     validationField需要过滤的字段 数组形式
    const _newParams = JSON.parse(JSON.stringify(data));
    for (const param in data) {
        if (_newParams[param] == '' || _newParams[param].length == 0 && (validationField == undefined || validationField.includes(param))) {
            delete _newParams[param];
        }
    }
    return _newParams;
}

export function get_uuid(len, radix) { //  指定长度和基数
    const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
    const uuid = [];
    let i;
    radix = radix || chars.length;

    if (len) {
        // Compact form
        for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random() * radix];
    } else {
        // rfc4122, version 4 form
        let r;

        // rfc4122 requires these characters
        uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
        uuid[14] = '4';

        // Fill in random data.  At i==19 set the high bits of clock sequence as
        // per rfc4122, sec. 4.1.5
        for (i = 0; i < 36; i++) {
            if (!uuid[i]) {
                r = 0 | Math.random() * 16;
                uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
            }
        }
    }
    return uuid.join('');
}

/**
 * @descripition 将后台返回的表头数据，转换为ant-design的表头数据
 * @param {Object} targetObj 需要转换的表头对象
 * @returns {Array} 转换为ant-design的表头数据
 */
export function switch_to_ant_tableheader(targetObj = {}) {
    const _resObj = [];
    for (const key in targetObj) {
        const _temObj = {
            'title': targetObj[key],
            'dataIndex': key,
            'scopedSlots': { 'customRender': key},
            'align': 'center'
        };
        _resObj.push(_temObj);
    }
    return _resObj;
}

// 验证数组重复
export function is_array_repeat(arr) {
    const hash = {};
    for (const i in arr) {
        if (hash[arr[i]])
            return true;
        hash[arr[i]] = true;
    }
    return false;
}

/**
* @desc   渲染表头数据
* @param  item通常用于在表格末尾插入操作一栏
* @author yintao 2020/6/9 11:17 上午
*/
export function render_header_data(data, item, position = 'center') {
    let headerData = {};
    const keys = Object.keys(data);
    const value = Object.values(data);
    headerData = keys.map((item, index) => ({
        title: value[index],
        dataIndex: item,
        key: item,
        align: position,
        scopedSlots: {customRender: item}
    }));
    if (item) {
        headerData.push(item);
    }
    return headerData;
}
