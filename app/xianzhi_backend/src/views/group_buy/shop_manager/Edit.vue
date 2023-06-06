<template>
    <div class="row">
        <a-page-header title="新增店铺管理员" @back="$router.go(-1)"/>
        <div class="qm-form flex t-120" ref="groupBuyShopManagerTable" style="height:90vh;overflow-y:scroll;">
            <a-form-model
                ref="ruleForm"
                :model="managerInfo"
                :label-col="labelCol"
                :wrapper-col="wrapperCol"
                :rules="rules"
                layout="horizontal"
            >
                <a-form-model-item label="管理员昵称">
                    <a-select
                        mode="multiple"
                        label-in-value
                        :value="selectedManager"
                        placeholder="请选择管理员"
                        style="width: 100%"
                        :filter-option="false"
                        :not-found-content="modalData.fetching ? undefined : null"
                        @search="fetch_user_list"
                        @change="handleManagerChange"
                    >
                        <a-spin v-if="modalData.fetching" slot="notFoundContent" size="small" />
                        <a-select-option v-for="(item, index) in userList" :key="index" :value="item.id">{{item.nickname}}</a-select-option>
                    </a-select>
                </a-form-model-item>
                <a-form-model-item :wrapper-col="{ span: 4, offset: 0 }" label="角色">
                    <a-radio-group name="radioGroup" v-model="managerInfo.role_id" @change="handle_change_role">
                        <a-radio value="0">普通管理员</a-radio>
                        <a-radio value="1">超级管理员</a-radio>
                    </a-radio-group>
                </a-form-model-item>
                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" @click="handle_save" :loading="submitFlag">保存</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyShopManagerTable">
            <img :src="perImage" />
        </a-modal>
    </div>
</template>

<script>
import {
    api_content_management_groupbuy_shop_manager_add,
} from '@/http/content-management/group_buy/list';
import {
    api_content_management_user_list
} from '@/http/content-management/user/list';
import debounce from 'lodash/debounce';

export default {
    name: 'groupBuyShopManagerEdit',
    components: {
    },
    data() {
        this.fetch_shop_list = debounce(this.fetch_user_list, 1000);
        return {
            selectedManager: [],
            visible: false,
            perImage: '',
            rules: {},
            labelCol: {span: 4},
            wrapperCol: {span: 14},
            submitFlag: false,
            managerInfo: {
                id: 0,
                shop_id: 0,
                uid: 0,
                role_id: 0,
            },
            userList: [],
            modalData: {
                visible: false,
                fetching: false,
            },
        };
    },
    beforeRouteLeave(to, form, next) {
        next();
    },
    mounted() {
        this.managerInfo.shop_id = this.$route.query.shop_id;
    },
    computed: {
    },
    methods: {
        // 提交
        async handle_save() {
            const _res = await api_content_management_groupbuy_shop_manager_add(this.managerInfo);
            if (_res.result){
                if(this.managerInfo.id==0){
                    this.$router.replace({
                        path: '/groupBuy-managerList?shop_id='+this.managerInfo.shop_id
                    });
                    return
                }
                this.$antdMessage.success(_res.message);
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        async fetch_user_list(keyword) {
            if (keyword == ""){
                return
            }
            this.userList = []
            this.modalData.fetching = true
            const _res = await api_content_management_user_list({keyword: keyword});
            if (_res.result){
                this.userList = _res.data.list
                this.modalData.fetching = false
            }
        },
        handleManagerChange(userArr) {
            this.userList = []
            this.modalData.fetching = false
            this.selectedManager = userArr
            if(userArr.length>0){
                this.managerInfo.uid = userArr[0].key
            }else{
                this.managerInfo.uid = 0
            }
        },
        handle_change_role(e){
            this.managerInfo.role_id = e.target.value
        },
    }
};
</script>

<style type="text/css">
    .pic_list{
        width: 100%;
        text-align: left;
    }
    .pic_item{
        width: 80px;
        height: 80px;
        margin: 2px;
        float: left;
    }
    .view-in-advance{
        width: 60px;
        height: 60px;
        margin: 2px;
    }
    .pre-image{
        width: 100%;
    }
    .upload{
        margin-top: 20px;
    }

    .img-modal .ant-modal-content img {
        background-color: transparent !important;
        -webkit-box-shadow: none;
        box-shadow: none;
        width: 100%;
     }
    .icon-del {
        font-size: 18px;
        color: #F00;
        vertical-align: sub;
    }
</style>
