<template>
    <div class="row">
        <a-page-header title="编辑团购" @back="$router.go(-1)"/>
        <div class="qm-form flex t-120" ref="groupBuyProductTable" style="height:90vh;overflow-y:scroll;">
            <a-form-model
                ref="ruleForm"
                :model="productInfo"
                :label-col="labelCol"
                :wrapper-col="wrapperCol"
                :rules="rules"
                layout="horizontal"
            >
                <a-form-model-item label="标题">
                    <a-input v-model="productInfo.title"/>
                </a-form-model-item>
                <a-form-model-item label="副标题">
                    <a-input v-model="productInfo.sub_title"/>
                </a-form-model-item>
                <a-form-model-item label="商家">
                    <a-select
                        mode="multiple"
                        label-in-value
                        :value="selectedShop"
                        placeholder="请选择商家"
                        style="width: 100%"
                        :filter-option="false"
                        :not-found-content="modalData.fetching ? undefined : null"
                        @search="fetch_shop_list"
                        @change="handleShopChange"
                    >
                        <a-spin v-if="modalData.fetching" slot="notFoundContent" size="small" />
                        <a-select-option v-for="(item, index) in shopList" :key="index" :value="item.id">{{item.shop_name}}</a-select-option>
                    </a-select>
                </a-form-model-item>
                <a-form-model-item label="套餐详情">
                    <vue-editor v-model="productInfo.info" useCustomImageHandler @image-added="handleImageAdded"/>
                </a-form-model-item>

                <a-form-model-item label="使用说明">
                    <vue-editor v-model="productInfo.rule_info" useCustomImageHandler @image-added="handleImageAdded"/>
                </a-form-model-item>

                <a-form-model-item label="图片">
                    <div class="pic_list">
                        <template v-for="(image_url,index) in productInfo.pics">
                            <div class="pic_item" :key="image_url">
                                <img :src="image_url" @click="visible = true;perImage = image_url" class="view-in-advance" />
                                <a-button size="small" icon="delete" @click="handle_delete(index)">删除</a-button>
                            </div>
                        </template>
                    </div>
                    <a-upload list-type="picture" :showUploadList="false" :customRequest="file => uplod_file(file)" accept="image/*">
                        <a-button size="small" icon="upload" :loading="uploadLoading" class="upload">上传</a-button>
                    </a-upload>
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 4, offset: 0 }" label="活动时间" >
                    <a-range-picker  @change="handle_time_change" v-model="activityDate"/>
                </a-form-model-item>

                <a-form-model-item label="价格">
                    <a-input v-model="productInfo.price" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="原价">
                    <a-input v-model="productInfo.original_price" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="退款类型" prop="platform">
                    <a-select v-model="productInfo.refund_type" placeholder="请选择">
                        <a-select-option
                            v-for="(item, index) in refundTypeList"
                            :key="index"
                            :value="item.value"
                        >{{ item.label}}
                        </a-select-option>
                    </a-select>
                </a-form-model-item>

                <a-form-model-item label="限量">
                    <a-input v-model="productInfo.max_num" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="每人限购">
                    <a-input v-model="productInfo.max_user_buy_num" type="number"/>
                </a-form-model-item>

                <a-form-model-item  :wrapper-col="{ span: 4, offset: 0 }" label="商家佣金比例">
                    <a-input class="w-200"  v-model="productInfo.shop_commission_rate" type="number"/> %
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 3, offset: 0 }" label="是否分销">
                    <a-radio-group name="radioGroup" v-model="productInfo.is_distribute" @change="handle_change_distribute">
                        <a-radio value="0">不分销</a-radio>
                        <a-radio value="1">分销</a-radio>
                    </a-radio-group>
                </a-form-model-item>

                <a-form-model-item  :wrapper-col="{ span: 4, offset: 0 }" label="佣金比例" v-if="productInfo.is_distribute==1">
                    <a-input class="w-200"  v-model="productInfo.commission_rate" type="number"/> %
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" @click="handle_save" :loading="submitFlag">保存</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyProductTable">
            <img :src="perImage" />
        </a-modal>
    </div>
</template>

<script>
import moment from 'moment';
import {
    api_content_management_groupbuy_product_info,
    api_content_management_groupbuy_product_save,
    api_content_management_groupbuy_shop_list,
} from '@/http/content-management/group_buy/list';
import { Upload } from 'ant-design-vue';
import { api_upload } from '@/http/global';
import { VueEditor } from "vue2-editor";
import debounce from 'lodash/debounce';

export default {
    name: 'groupBuyProductEdit',
    components: {
        'a-upload': Upload,
        'vue-editor': VueEditor
    },
    data() {
        this.fetch_shop_list = debounce(this.fetch_shop_list, 500);
        return {
            activityDate: null,
            uploadLoading: false,
            visible: false,
            perImage: '',
            rules: {},
            labelCol: {span: 4},
            wrapperCol: {span: 14},
            submitFlag: false,
            productInfo: {
                id: 0,
            },
            refundTypeList: [],
            shopList: [],
            selectedShop: [],
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
        this.productInfo.id = this.$route.query.id;
        this.init_data();
    },
    computed: {

    },
    methods: {
        async init_data(){
            const _res = await api_content_management_groupbuy_product_info({ id: this.productInfo.id });
            if (_res.result){
                this.productInfo = _res.data.productInfo;
                this.refundTypeList = _res.data.refundTypeList;
                if(this.productInfo.id!=0 && this.productInfo.start_date!="" && this.productInfo.end_date!=""){
                    let startDate = moment(this.productInfo.start_date, "YYYY-MM-DD")
                    let endDate = moment(this.productInfo.end_date, "YYYY-MM-DD")
                    this.activityDate = [startDate, endDate];
                }
                if(this.productInfo.id!=0 && this.productInfo.shop_id!=0){
                    this.selectedShop = {key:this.productInfo.shop_id, label: this.productInfo.shop_name}
                }
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 提交
        async handle_save() {
            const _res = await api_content_management_groupbuy_product_save(this.productInfo);
            if (_res.result){
                if(this.productInfo.id==0){
                    this.$router.replace({
                        path: '/groupBuy-productList'
                    });
                    return
                }
                this.$antdMessage.success(_res.message);
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        async handleImageAdded(file, Editor, cursorLocation, resetUploader) {
            let _formData = new FormData();
            _formData.append('action', 'upload');
            _formData.append('file', file);
            _formData.append('page_type', 'group_buy');
            let _res = await api_upload(_formData);
            if (!_res.result) {
                return;
            }
            Editor.insertEmbed(cursorLocation, "image", _res.data.url);
            resetUploader();
        },
        async fetch_shop_list(keyword) {
            if (keyword == ""){
                return
            }
            this.shopList = []
            this.modalData.fetching = true
            const _res = await api_content_management_groupbuy_shop_list({keyword: keyword});
            if (_res.result){
                this.shopList = _res.data.list
                this.modalData.fetching = false
            }
        },
        async uplod_file(option) { // 上传图片，目前只有一个上传按钮，此方法写死了一个字段，后期上传列多了可以灵活配置
            this.uploadLoading = true;
            const file = option.file;
            const _formData = new FormData();
            _formData.append('action', 'upload');
            _formData.append('file', file);
            _formData.append('page_type', 'group_buy_product');
            const _res = await api_upload(_formData);
            if (!_res.result) {
                this.uploadLoading = false;
                return;
            }
            this.productInfo.pics.push(_res.data.url);
            this.$antdMessage.success(_res.message);
            this.uploadLoading = false;
        },
        handle_time_change(val) {
            this.productInfo.start_date = moment(val[0]).format(
                'YYYY-MM-DD'
            );
            this.productInfo.end_date = moment(val[1]).format(
                'YYYY-MM-DD'
            );
        },
        handle_delete(index){
            this.productInfo.pics.splice(index, 1);
        },
        handle_change_distribute(e){
            this.productInfo.is_distribute = e.target.value
        },
        handleShopChange(shopArr) {
            this.shopList = []
            this.modalData.fetching = false
            this.selectedShop = shopArr
            if(shopArr.length>0){
                this.productInfo.shop_id = shopArr[0].key
            }else{
                this.productInfo.shop_id = 0
            }
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
