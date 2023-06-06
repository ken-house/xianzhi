<template>
    <div class="row">
        <a-page-header title="商品购买选项编辑" @back="$router.go(-1)"/>
        <div class="qm-form flex t-120" ref="groupBuyProductOptionTable" style="height:90vh;overflow-y:scroll;">
            <a-form-model
                ref="ruleForm"
                :model="optionInfo"
                :label-col="labelCol"
                :wrapper-col="wrapperCol"
                :rules="rules"
                layout="horizontal"
            >
                <a-form-model-item label="选项名">
                    <a-input v-model="optionInfo.name" />
                </a-form-model-item>

                <a-form-model-item label="价格">
                    <a-input v-model="optionInfo.price" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="原价">
                    <a-input v-model="optionInfo.original_price" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="限量">
                    <a-input v-model="optionInfo.max_num" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="排序">
                    <a-input v-model="optionInfo.sort" type="number"/>
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 4, offset: 0 }" label="状态">
                    <a-radio-group name="radioGroup" v-model="optionInfo.status" @change="handle_change_status">
                        <a-radio value="0">删除</a-radio>
                        <a-radio value="1">有效</a-radio>
                    </a-radio-group>
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" @click="handle_save" :loading="submitFlag">保存</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyProductOptionTable">
            <img :src="perImage" />
        </a-modal>
    </div>
</template>

<script>
import {
    api_content_management_groupbuy_product_option_save,api_content_management_groupbuy_product_option_info
} from '@/http/content-management/group_buy/list';

export default {
    name: 'groupBuyProductOptionEdit',
    components: {
    },
    data() {
        return {
            visible: false,
            perImage: '',
            rules: {},
            labelCol: {span: 4},
            wrapperCol: {span: 14},
            submitFlag: false,
            optionInfo: {
                id: 0,
                product_id: 0,
                status: 0,
            },
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
        this.optionInfo.product_id = this.$route.query.product_id;
        this.optionInfo.id = this.$route.query.id;
        if(this.optionInfo.id!=0){
            this.init_data();
        }
    },
    computed: {

    },
    methods: {
        async init_data(){
            const _res = await api_content_management_groupbuy_product_option_info({ id: this.optionInfo.id });
            if (_res.result){
                this.optionInfo = _res.data.optionInfo;
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 提交
        async handle_save() {
            const _res = await api_content_management_groupbuy_product_option_save(this.optionInfo);
            if (_res.result){
                if(this.optionInfo.id==0){
                    this.$router.replace({
                        path: '/groupBuy-productOptionList?id='+this.optionInfo.product_id
                    });
                    return
                }
                this.$antdMessage.success(_res.message);
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        handle_change_status(e){
            this.optionInfo.status = e.target.value
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
