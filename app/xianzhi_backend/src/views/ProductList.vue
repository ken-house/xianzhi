<template>
    <div class="row">
        <div class="qm-form auto">
            <a-form-model labelAlign="left" layout="inline" aria-set  :size="$store.state.antd.modelSize" :form="formData">
                <a-form-model-item label="商品id">
                    <a-input  :size="$store.state.antd.modelSize" v-model="formData.id" allowClear @keyup.native.enter="form_change"></a-input>
                </a-form-model-item>
                <a-form-model-item label="商品名称">
                    <a-input  :size="$store.state.antd.modelSize" v-model="formData.name" allowClear @keyup.native.enter="form_change"></a-input>
                </a-form-model-item>
                <a-form-model-item label="用户UID">
                    <a-input  :size="$store.state.antd.modelSize" v-model="formData.uid" allowClear @keyup.native.enter="form_change"></a-input>
                </a-form-model-item>
                <a-form-model-item label="分类">
                    <a-select
                        mode="multiple"
                        label-in-value
                        :value="selectedCategory"
                        placeholder="请选择分类"
                        class="w-200"
                        :filter-option="false"
                        :size="$store.state.antd.modelSize"
                        :not-found-content="modalData.fetching ? undefined : null"
                        @search="fetch_category_list"
                        @change="handleCategorySearch"
                    >
                        <a-spin v-if="modalData.fetching" slot="notFoundContent" size="small" />
                        <a-select-option v-for="(item, index) in categoryList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                    </a-select>
                </a-form-model-item>
                <a-form-model-item label="状态">
                    <a-select class="w-120" v-model="formData.status" placeholder="请选择"  :size="$store.state.antd.modelSize" @change="form_change">
                        <a-select-option v-for="(item, index) in statusList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                    </a-select>
                </a-form-model-item>
                <a-form-model-item label="选择日期">
                    <a-range-picker class="w-200"  :size="$store.state.antd.modelSize" v-model="searchDate"  @change="handle_time_change" />
                </a-form-model-item>
                <a-form-model-item>
                    <a-button  :size="$store.state.antd.modelSize" type="primary" @click="form_change">查询</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <div ref="productTable">
            <a-table :columns="tableHeader" rowKey="id" :data-source="tableData" :pagination="pageConfig" :scroll="{y: 'calc(100vh - 200px)',x: 'calc(100vw - 10W)'}">
                <div slot="nickname" slot-scope="text,record">
                    {{record.nickname}}({{record.uid}})
                </div>
                <div slot="product_title" slot-scope="text">
                    <p class="ellipsis-text">{{text}}</p>
                </div>
                <div slot="info" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text">{{text}}</p>
                    </a-popover>
                </div>
                <div class="pic_list" slot="pics" slot-scope="text">
                    <template v-for="image_url in text">
                        <img :src="image_url" @click="visible = true;perImage = image_url" class="view-in-advance" :key="image_url"/>
                    </template>
                </div>
                <template slot="operator" slot-scope="text, record, index">
                    <div class="editable-row-operations">
                        <a @click="handle_show_modal('审核通过', 'pass', record, index)" v-if="record.status=='0'">通过</a>
                        <a @click="handle_show_modal('审核不通过', 'refuse', record, index)"  v-if="record.status=='0'">不通过</a>
                        <a @click="handle_show_modal('强制下架', 'down', record, index)"  v-if="record.status=='1'">强制下架</a>
                        <a @click="handle_show_modal('修改分类', 'category', record, index)"  v-if="record.status=='1'">修改分类</a>
                        <a @click="handle_show_modal('置顶','stick',record, index)" v-if="record.status=='1' && record.stick_status=='0'">置顶</a>
                        <a @click="handle_show_modal('取消置顶','cancel_stick',record, index)" v-if="record.status=='1' && record.stick_status=='1'">取消置顶</a>
                        <a @click="handle_show_modal('查看原因','reason',record, index)" v-if="record.status=='2' || record.status=='3'">查看原因</a>
                    </div>
                </template>
            </a-table>
            <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.productTable">
                <img :src="perImage" class="pre-image"/>
            </a-modal>
            <a-modal class="repeat-config-modal" width="500px" v-model="modalData.visible" :title="modalData.title" :afterClose="() => { reset_modal() }" :maskClosable="false">
                <template slot="footer">
                    <a-button key="back" @click="modalData.visible = false">取消</a-button>
                    <a-button key="submit" type="primary" :loading="modalData.loading" @click="handle_modal_confirm(modalData)"  v-if="modalData.type!='reason'">确定</a-button>
                </template>
                <div class="modal-content">
                    <div class="form">
                        <template v-if="modalData.type=='pass'">
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">是否作弊：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select class="w-120" v-model="modalData.is_cheat" placeholder="请选择" :size="$store.state.antd.modelSize">
                                        <a-select-option v-for="(item, index) in cheatList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">选择分类：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select
                                        mode="multiple"
                                        label-in-value
                                        :value="modalData.selectedCategory"
                                        placeholder="请选择分类"
                                        style="width: 100%"
                                        :filter-option="false"
                                        :not-found-content="modalData.fetching ? undefined : null"
                                        @search="fetch_category_list"
                                        @change="handleCategoryChange"
                                    >
                                        <a-spin v-if="modalData.fetching" slot="notFoundContent" size="small" />
                                        <a-select-option v-for="(item, index) in categoryList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='refuse'">
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">不通过原因：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select class="w-300" v-model="modalData.reason_id" placeholder="请选择" :size="$store.state.antd.modelSize">
                                        <a-select-option v-for="(item, index) in reasonList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='category'">
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">选择分类：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select
                                        mode="multiple"
                                        label-in-value
                                        :value="modalData.selectedCategory"
                                        placeholder="请选择分类"
                                        style="width: 100%"
                                        :filter-option="false"
                                        :not-found-content="modalData.fetching ? undefined : null"
                                        @search="fetch_category_list"
                                        @change="handleCategoryChange"
                                    >
                                        <a-spin v-if="modalData.fetching" slot="notFoundContent" size="small" />
                                        <a-select-option v-for="(item, index) in categoryList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='stick'">
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">选择页面：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select class="w-120" v-model="modalData.page_type" placeholder="请选择"  :size="$store.state.antd.modelSize">
                                        <a-select-option v-for="(item, index) in pageTypeList" :key="index" :value="item.value">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                            <a-row :gutter="[0, 10]" v-if="modalData.page_type==3">
                                <a-col :span="6">
                                    <p class="label">选择活动：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-select class="w-120" v-model="modalData.activity_id" placeholder="请选择"  :size="$store.state.antd.modelSize">
                                        <a-select-option v-for="(item, index) in activityList" :key="index" :value="item.label">{{item.label}}</a-select-option>
                                    </a-select>
                                </a-col>
                            </a-row>
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">选择时间：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-range-picker class="w-300"  :size="$store.state.antd.modelSize" v-model="stickDate"  @change="handle_stick_time_change" />
                                </a-col>
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='cancel_stick'">
                            <a-row :gutter="[0, 10]">
                                确定取消置顶吗？
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='down'">
                            <a-row :gutter="[0, 10]">
                                <a-col :span="6">
                                    <p class="label">违规原因：</p>
                                </a-col>
                                <a-col :span="16">
                                    <a-input :size="$store.state.antd.modelSize" v-model="modalData.reason"></a-input>
                                </a-col>
                            </a-row>
                        </template>

                        <template v-if="modalData.type=='reason'">
                            <a-row :gutter="[0, 10]">
                                {{modalData.reason}}
                            </a-row>
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
import { api_content_management_product_list,api_content_management_product_pass,api_content_management_product_refuse, api_content_management_product_down, api_content_management_product_category, api_content_management_product_activity, api_content_management_product_stick, api_content_management_product_category_list } from '@/http/content-management/product/list';
import { render_header_data } from '@/assets/js/tools';

export default {
    data() {
        this.fetch_category_list = debounce(this.fetch_category_list, 500);
        return {
            selectedCategory: [],
            searchDate: null,
            formData: {
                page: '1',
                page_size: '10',
                status: '0',
                id: '',
                uid: '',
                name: '',
                category_id: '',
                start_date: '',
                end_date: ''
            },
            tableHeader: [],
            tableData: [],
            pageConfig: {
                total: 0,
                pageSize: 10,
                onChange: (current) => { this.handle_page_change(current); },
            },
            statusList: [],
            cheatList: [],
            categoryList: [],
            reasonList: [],
            pageTypeList: [],
            activityList: [],
            visible: false,
            perImage: '',
            stickDate: null,
            modalData: {
                id: 0,
                type: '',
                title: '',
                index: 0,
                reason_id: '',
                reason: '',
                is_cheat: '',
                category_id: '',
                activity_id: '',
                page_type: 0, // 置顶页面
                start_date: '', // 置顶开始时间
                end_date: '', // 置顶结束时间
                visible: false,
                loading: false,
                fetching: false,
                selectedCategory: [],
            },
        };
    },
    mounted() {
        const today = moment(new Date());
        this.searchDate = [today, today];
        this.formData.start_date = today.format('YYYY-MM-DD');
        this.formData.end_date = today.format('YYYY-MM-DD');
        this.init_data();
    },
    methods: {
        async fetch_category_list(keyword) {
            if (keyword == ""){
                return
            }
            this.categoryList = []
            this.modalData.fetching = true
            const _res = await api_content_management_product_category_list({keyword: keyword});
            if (_res.result){
                this.categoryList = _res.data.categoryList
                this.modalData.fetching = false
            }
        },
        handleCategorySearch(categoryArr) {
            this.categoryList = []
            this.modalData.fetching = false
            this.selectedCategory = categoryArr
            if(categoryArr.length>0){
                this.formData.category_id = categoryArr[0].key
            }else{
                this.formData.category_id = 0
            }
        },
        handleCategoryChange(categoryArr) {
            this.categoryList = []
            this.modalData.fetching = false
            this.modalData.selectedCategory = categoryArr
            if(categoryArr.length>0){
                this.modalData.category_id = categoryArr[0].key
            }else{
                this.modalData.category_id = 0
            }
        },

        async init_data(){
            const _res = await api_content_management_product_list(this.formData);
            if (_res.result){
                this.tableHeader = render_header_data(_res.data.headerData, {key: 'operator', title: '操作', scopedSlots: {customRender: 'operator'}});
                this.set_column_width(this.tableHeader);
                this.pageConfig.total = Number(_res.data.count);
                this.tableData = _res.data.list
                this.statusList = _res.data.statusList
                this.cheatList = _res.data.cheatList
                this.reasonList = _res.data.reasonList
                this.activityList = _res.data.activityList
                this.pageTypeList = _res.data.pageTypeList
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 操作
        async handle_modal_confirm(modalData){
            switch (modalData.type){
                case 'pass':
                    var _params = { id: this.modalData.id, is_cheat: this.modalData.is_cheat, category_id: this.modalData.category_id};
                    var _res = await api_content_management_product_pass(_params);
                    break;
                case 'refuse':
                    _params = {  id: this.modalData.id, reason_id: this.modalData.reason_id };
                    _res = await api_content_management_product_refuse(_params);
                    break;
                case 'down':
                    _params = {  id: this.modalData.id, audit_reason: this.modalData.reason};
                    _res = await api_content_management_product_down(_params);
                    break;
                case 'category':
                    _params = {  id: this.modalData.id, category_id: this.modalData.category_id };
                    _res = await api_content_management_product_category(_params);
                    break;
                case 'activity':
                    var _params = { id: this.modalData.id, activity_id: this.modalData.activity_id};
                    var _res = await api_content_management_product_activity(_params);
                    break;
                case 'stick':
                    var _params = { id: this.modalData.id, status: 1, type:this.modalData.page_type, activity_id:this.modalData.activity_id, start_date: this.modalData.start_date, end_date: this.modalData.end_date};
                    var _res = await api_content_management_product_stick(_params);
                    break;
                case 'cancel_stick':
                    var _params = { id: this.modalData.id, status: 0};
                    var _res = await api_content_management_product_stick(_params);
                    break;
            }
            if (_res.result){
                this.$antdMessage.success(_res.message);
                this.modalData.visible = false;
                if (modalData.type=="pass" || modalData.type=="refuse" || modalData.type=="down"){
                    this.tableData.splice(modalData.index, 1);
                }else{
                    this.init_data()
                }
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 显示弹窗
        async handle_show_modal(title, type, record, index){
            this.modalData.id = record.id;
            this.modalData.type = type;
            this.modalData.title = title;
            this.modalData.index = index;
            this.modalData.reason_id = record.reason_id;
            this.modalData.reason = record.audit_reason;
            this.modalData.category_id = record.category_id;
            this.modalData.page_type = record.page_type;
            this.modalData.activity_id = record.activity_id;
            this.modalData.start_date = record.stick_start_date;
            this.modalData.end_date = record.stick_end_date;
            this.modalData.visible = true;
            this.modalData.fetching = false;
            this.modalData.selectedCategory = []
        },
        // 重置屏蔽词弹窗
        reset_modal(){
            this.modalData.id = 0;
            this.modalData.type = '';
            this.modalData.title = '';
            this.modalData.index = 0;
            this.modalData.reason_id = '';
            this.modalData.reason = '';
            this.modalData.is_cheat = '';
            this.modalData.category_id = '';
            this.modalData.page_type = 0;
            this.modalData.activity_id = '';
            this.stickDate = null;
            this.modalData.start_date = '';
            this.modalData.end_date = '';
            this.modalData.visible = false;
            this.modalData.fetching = false;
            this.modalData.selectedCategory = [];
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
        handle_time_change(val){
            if (val.length){
                this.formData.start_date = moment(val[0]).format('YYYY-MM-DD');
                this.formData.end_date = moment(val[1]).format('YYYY-MM-DD');
            } else {
                this.formData.start_date = '';
                this.formData.end_date = '';
            }
            this.formData.page = 1;
            this.init_data();
        },
        handle_stick_time_change(val){
            if (val.length){
                this.modalData.start_date = moment(val[0]).format('YYYY-MM-DD');
                this.modalData.end_date = moment(val[1]).format('YYYY-MM-DD');
            } else {
                this.modalData.start_date = '';
                this.modalData.end_date = '';
            }
        },
        // 表格排版
        set_column_width() {
            const widthData = {
                id: 50,
                nickname: 80,
                name: 80,
                product_title: 100,
                info: 100,
                pics: 200,
                category_name: 50,
                price: 50,
                location: 100,
                updated_at: 80,
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
</style>