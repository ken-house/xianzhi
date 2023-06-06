<template>
    <div class="row">
        <div class="qm-form auto">
            <a-form-model labelAlign="left" layout="inline" aria-set  :size="$store.state.antd.modelSize" :form="formData">
                <a-form-model-item label="商品id">
                    <a-input  :size="$store.state.antd.modelSize" v-model="formData.id" allowClear @keyup.native.enter="form_change"></a-input>
                </a-form-model-item>
                <a-form-model-item label="商品名称">
                    <a-input  :size="$store.state.antd.modelSize" v-model="formData.title" allowClear @keyup.native.enter="form_change"></a-input>
                </a-form-model-item>
                <a-form-model-item label="状态">
                    <a-select class="w-120" v-model="formData.status" placeholder="请选择"  :size="$store.state.antd.modelSize" @change="form_change">
                        <a-select-option v-for="(item, index) in statusList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                    </a-select>
                </a-form-model-item>
                <a-form-model-item>
                    <a-button  :size="$store.state.antd.modelSize" type="primary" @click="form_change">查询</a-button>
                </a-form-model-item>

                <a-form-model-item>
                    <a-button  :size="$store.state.antd.modelSize" type="default" @click="handle_jump(0)">创建团购</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <div ref="groupBuyProductTable">
            <a-table :columns="tableHeader" rowKey="id" :data-source="tableData" :pagination="pageConfig" :scroll="{y: 'calc(100vh - 250px)',x: 'calc(100vw - 10px)'}" style="overflow:auto;">
                <div slot="product_title" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div slot="sub_title" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div slot="info" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div slot="rule_info" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div class="pic_list" slot="pics" slot-scope="text">
                    <template v-for="image_url in text">
                        <img :src="image_url" @click="visible = true;perImage = image_url" class="view-in-advance" :key="image_url"/>
                    </template>
                </div>
                <div slot="shop_commission_rate" slot-scope="text">
                    <p v-if="text=='0%'"></p>
                    <p class="red" v-else>{{text}}</p>
                </div>
                <template slot="operator" slot-scope="text, record, index">
                    <div class="editable-row-operations">
                        <a @click="handle_show_modal('上架', 'up', record, index, 1)" v-if="record.status!='1'">上架</a>
                        <a @click="handle_show_modal('下架', 'down', record, index, 2)"  v-if="record.status=='1'">下架</a>
                        <a class="qm-btn" href="javascript:;" @click="handle_jump(record.id)">编辑</a>
                        <a class="qm-btn" href="javascript:;" @click="handle_jump_option(record.id)">购买选项</a>
                    </div>
                </template>
            </a-table>
            <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyProductTable">
                <img :src="perImage" class="pre-image"/>
            </a-modal>
            <a-modal class="repeat-config-modal" width="500px" v-model="modalData.visible" :title="modalData.title" :afterClose="() => { reset_modal() }" :maskClosable="false">
                <template slot="footer">
                    <a-button key="back" @click="modalData.visible = false">取消</a-button>
                    <a-button key="submit" type="primary" :loading="modalData.loading" @click="handle_modal_confirm(modalData)"  v-if="modalData.type!='reason'">确定</a-button>
                </template>
                <div class="modal-content">
                    <div class="form">
                        <template>
                            <a-row :gutter="[0, 10]" v-if="modalData.type=='up'">确定上架吗？</a-row>
                            <a-row :gutter="[0, 10]" v-if="modalData.type=='down'">确定下架吗？</a-row>
                        </template>
                    </div>
                </div>
            </a-modal>
        </div>
    </div>
</template>
<script>
import moment from 'moment';
import debounce from 'lodash/debounce';
import { api_content_management_groupbuy_product_list, api_content_management_groupbuy_product_status} from '@/http/content-management/group_buy/list';
import { render_header_data } from '@/assets/js/tools';

export default {
    data() {
        return {
            formData: {
                page: '1',
                page_size: '10',
                status: '-1',
                id: '',
                title: '',
            },
            tableHeader: [],
            tableData: [],
            pageConfig: {
                total: 0,
                pageSize: 10,
                onChange: (current) => { this.handle_page_change(current); },
            },
            statusList: [],
            visible: false,
            perImage: '',
            modalData: {
                id: 0,
                type: '',
                status: 0,
                visible: false,
                fetching: false,
            },
        };
    },
    mounted() {
        this.init_data();
    },
    methods: {
        handle_jump(id){
            this.$router.push({
                path: 'groupBuy-productEdit',
                query: {
                    id: id
                }
            });
        },
        handle_jump_option(id){
            this.$router.push({
                path: 'groupBuy-productOptionList',
                query: {
                    id: id
                }
            });
        },
        async init_data(){
            const _res = await api_content_management_groupbuy_product_list(this.formData);
            if (_res.result){
                this.tableHeader = render_header_data(_res.data.headerData, {key: 'operator', title: '操作', scopedSlots: {customRender: 'operator'}});
                this.set_column_width(this.tableHeader);
                this.pageConfig.total = Number(_res.data.count);
                this.tableData = _res.data.list
                this.statusList = _res.data.statusList
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 操作
        async handle_modal_confirm(modalData){
            let _params = { id: this.modalData.id, status: this.modalData.status }
            let _res = await api_content_management_groupbuy_product_status(_params)
            if (_res.result){
                this.$antdMessage.success(_res.message);
                this.modalData.visible = false;
                this.init_data()
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 显示弹窗
        async handle_show_modal(title, type, record, index, status){
            this.modalData.id = record.id;
            this.modalData.type = type;
            this.modalData.title = title;
            this.modalData.index = index;
            this.modalData.status = status;
            this.modalData.visible = true;
            this.modalData.fetching = false;
        },
        // 重置屏蔽词弹窗
        reset_modal(){
            this.modalData.id = 0;
            this.modalData.type = '';
            this.modalData.title = '';
            this.modalData.index = 0;
            this.modalData.status = 0;
            this.modalData.visible = false;
            this.modalData.fetching = false;
        },
        // 翻页
        handle_page_change(val){
            this.formData.page = val;
            this.init_data();
        },
        form_change(){
            this.formData.page = 1;
            this.init_data();
        },
        // 表格排版
        set_column_width() {
            const widthData = {
                id: 50,
                product_title: 100,
                shop_name: 100,
                sub_title: 80,
                pics: 300,
                price: 100,
                activity_time: 200,
                limit_num: 100,
                sale_num: 100,
                shop_commission_rate: 100,
                is_distribute: 100,
                commission_rate: 100,
                refund_type: 100,
                status_name: 80,
                operator: 150,
            };
            this.tableHeader.forEach((n, i) => {
                n.width = widthData[n.key];
            });
        }
    }
};
</script>

<style type="text/css">
    .qm-form{
        text-align: left;
        padding: 0px 30px;
    }
    .ellipsis-text {
        width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .popover-content {
        width: 350px;
        word-break: break-all;
        height: 200px;
        overflow-y: scroll;
        padding: 0px 10px;
    }
    .qm-tips {
        margin-left: 20px;
    }
    .pic_list{
        width: 200px;
        text-align: left;
    }
    .view-in-advance{
        width: 60px;
        height: 60px;
        margin: 2px;
    }
    .pre-image{
        width: 100%;
    }
    .red{
        color: #F00;
    }
</style>