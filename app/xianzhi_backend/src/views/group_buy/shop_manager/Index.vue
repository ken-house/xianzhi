<template>
    <div class="row">
        <a-page-header title="管理员列表" @back="$router.go(-1)"/>
        <div class="qm-form auto">
            <a-form-model-item>
                <a-button  :size="$store.state.antd.modelSize" type="default" @click="handle_jump()">新增管理员</a-button>
            </a-form-model-item>
        </div>
        <div ref="groupBuyShopManagerTable">
            <a-table :columns="tableHeader" rowKey="id" :data-source="tableData" :pagination="pageConfig" :scroll="{y: 'calc(100vh - 250px)',x: 'calc(100vw - 10px)'}" style="overflow:auto;">
                <div slot="info" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div slot="location" slot-scope="text">
                    <a-popover trigger="click">
                        <template slot="content">
                            <p class="popover-content" v-html="text"></p>
                        </template>
                        <p class="ellipsis-text" v-html="text">{{text}}</p>
                    </a-popover>
                </div>
                <div slot="avatar" slot-scope="text">
                    <img :src="text" @click="visible = true;perImage = text" class="view-in-advance" />
                </div>
                <div slot="status_name" slot-scope="text, record">
                    <p class="red" v-if="record.status=='0' || record.status=='2'">{{text}}</p>
                    <p v-else>{{text}}</p>
                </div>
                <template slot="operator" slot-scope="text, record, index">
                    <div class="editable-row-operations">
                        <a @click="handle_show_modal('有效', 'up', record, index, 1)"  v-if="record.status=='0'">设为有效</a>
                        <a @click="handle_show_modal('无效', 'down', record, index, 0)"  v-if="record.status=='1'">设为无效</a>
                        <a @click="handle_show_modal('删除', 'del', record, index, 2)" v-if="record.status!='2'">删除</a>
                    </div>
                </template>
            </a-table>
            <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyShopManagerTable">
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
                            <a-row :gutter="[0, 10]" v-if="modalData.type=='up'">确定设为有效吗？</a-row>
                            <a-row :gutter="[0, 10]" v-if="modalData.type=='down'">确定设为无效吗？</a-row>
                            <a-row :gutter="[0, 10]" v-if="modalData.type=='del'">确定删除吗？</a-row>
                        </template>
                    </div>
                </div>
            </a-modal>
        </div>
    </div>
</template>
<script>
import { api_content_management_groupbuy_shop_manager_list, api_content_management_groupbuy_shop_manager_status} from '@/http/content-management/group_buy/list';
import { render_header_data } from '@/assets/js/tools';

export default {
    data() {
        return {
            shopId: 0,
            tableHeader: [],
            tableData: [],
            pageConfig: {
                total: 0,
                pageSize: 10,
                onChange: (current) => { this.handle_page_change(current); },
            },
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
        this.shopId = this.$route.query.shop_id;
        this.init_data();
    },
    methods: {
        handle_jump(){
            this.$router.push({
                path: '/groupBuy-managerEdit',
                query: {
                    shop_id: this.shopId,
                }
            });
        },
        async init_data(){
            const _res = await api_content_management_groupbuy_shop_manager_list({ id: this.shopId });
            if (_res.result){
                this.tableHeader = render_header_data(_res.data.headerData, {key: 'operator', title: '操作', scopedSlots: {customRender: 'operator'}});
                this.set_column_width(this.tableHeader);
                this.pageConfig.total = Number(_res.data.count);
                this.tableData = _res.data.list
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 操作
        async handle_modal_confirm(modalData){
            let _params = { id: this.modalData.id, status: this.modalData.status }
            let _res = await api_content_management_groupbuy_shop_manager_status(_params)
            if (_res.result){
                this.$antdMessage.success(_res.message);
                this.modalData.visible = false;
                if(this.modalData.type=="del"){
                    this.tableData.splice(modalData.index, 1);
                }else{
                    this.init_data()
                }
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
                uid: 100,
                avatar: 100,
                nickname: 100,
                phone: 150,
                status_name: 80,
                role_name: 100,
                updated_at: 200,
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