<template>
    <div class="row">
        <a-page-header title="编辑店铺" @back="$router.go(-1)"/>
        <div class="qm-form flex t-120" ref="groupBuyShopTable" style="height:90vh;overflow-y:scroll;">
            <a-form-model
                ref="ruleForm"
                :model="shopInfo"
                :label-col="labelCol"
                :wrapper-col="wrapperCol"
                :rules="rules"
                layout="horizontal"
            >
                <a-form-model-item label="店铺名">
                    <a-input v-model="shopInfo.shop_name"/>
                </a-form-model-item>
                <a-form-model-item label="店铺头像" :wrapper-col="{ span: 3, offset: 0 }">
                    <img :src="shopInfo.shop_avatar" @click="visible = true;perImage = shopInfo.shop_avatar" class="view-in-advance" />
                    <a-upload list-type="picture" :showUploadList="false" :customRequest="file => uplod_avatar(file)" accept="image/*">
                        <a-button size="small" icon="upload" :loading="uploadLoading" class="upload">上传</a-button>
                    </a-upload>
                </a-form-model-item>
                <a-form-model-item label="店铺LOGO" :wrapper-col="{ span: 3, offset: 0 }">
                    <img :src="shopInfo.shop_logo" @click="visible = true;perImage = shopInfo.shop_logo" class="view-in-advance" />
                    <a-upload list-type="picture" :showUploadList="false" :customRequest="file => uplod_logo(file)" accept="image/*">
                        <a-button size="small" icon="upload" :loading="uploadLoading" class="upload">上传</a-button>
                    </a-upload>
                </a-form-model-item>
                <a-form-model-item label="营业时间">
                    <a-input v-model="shopInfo.open_time"/>
                </a-form-model-item>
                <a-form-model-item label="联系电话">
                    <a-input v-model="shopInfo.phone"/>
                </a-form-model-item>
                <a-form-model-item label="位置">
                    <a-input v-model="shopInfo.location"/> <a target="_blank" href="https://jingweidu.bmcx.com/">查询坐标</a>
                </a-form-model-item>
                <a-form-model-item label="经度">
                    <a-input v-model="shopInfo.lng"/>
                </a-form-model-item>
                <a-form-model-item label="纬度">
                    <a-input v-model="shopInfo.lat"/>
                </a-form-model-item>
                <a-form-model-item label="店铺简介">
                    <vue-editor v-model="shopInfo.info" useCustomImageHandler @image-added="handleImageAdded"/>
                </a-form-model-item>

                <a-form-model-item label="图片">
                    <div class="pic_list" style="width:900px;">
                        <template v-for="(image_url,index) in shopInfo.pics">
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

                <a-form-model-item label="人均价格">
                    <a-input v-model="shopInfo.avg_price" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="评分">
                    <a-input v-model="shopInfo.score" type="number"/>
                </a-form-model-item>

                <a-form-model-item label="提现账户">
                    <a-input v-model="shopInfo.withdraw_account"/>
                </a-form-model-item>

                <a-form-model-item label="佣金比例" :wrapper-col="{ span: 4, offset: 0 }">
                    <a-input class="w-200" v-model="shopInfo.commission_rate"/> %
                </a-form-model-item>

                <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
                    <a-button type="primary" @click="handle_save" :loading="submitFlag">保存</a-button>
                </a-form-model-item>
            </a-form-model>
        </div>
        <a-modal v-model="visible" :footer="null" :closable="false" :wrapClassName="'img-modal'" :getContainer="() => $refs.groupBuyShopTable">
            <img :src="perImage" />
        </a-modal>
    </div>
</template>

<script>
import moment from 'moment';
import {
    api_content_management_groupbuy_shop_info,
    api_content_management_groupbuy_shop_save,
} from '@/http/content-management/group_buy/list';
import { Upload } from 'ant-design-vue';
import { api_upload } from '@/http/global';
import { VueEditor } from "vue2-editor";

export default {
    name: 'groupBuyShopEdit',
    components: {
        'a-upload': Upload,
        'vue-editor': VueEditor
    },
    data() {
        return {
            activityDate: null,
            uploadLoading: false,
            visible: false,
            perImage: '',
            rules: {},
            labelCol: {span: 4},
            wrapperCol: {span: 14},
            submitFlag: false,
            shopInfo: {
                id: 0,
            },
            shopList: [],
        };
    },
    beforeRouteLeave(to, form, next) {
        next();
    },
    mounted() {
        this.shopInfo.id = this.$route.query.shop_id;
        this.init_data();
    },
    computed: {
    },
    methods: {
        async init_data(){
            const _res = await api_content_management_groupbuy_shop_info({ id: this.shopInfo.id });
            if (_res.result){
                this.shopInfo = _res.data.shopInfo;
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        // 提交
        async handle_save() {
            const _res = await api_content_management_groupbuy_shop_save(this.shopInfo);
            if (_res.result){
                if(this.shopInfo.id==0){
                    this.$router.replace({
                        path: '/groupBuy-shopList'
                    });
                    return
                }
                this.$antdMessage.success(_res.message);
            } else {
                this.$antdMessage.error(_res.message);
            }
        },
        handle_delete(index){
            this.shopInfo.pics.splice(index, 1);
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
            this.shopInfo.pics.push(_res.data.url);
            this.$antdMessage.success(_res.message);
            this.uploadLoading = false;
        },

        async uplod_avatar(option) { // 上传图片，目前只有一个上传按钮，此方法写死了一个字段，后期上传列多了可以灵活配置
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
            this.shopInfo.shop_avatar = _res.data.url;
            this.$antdMessage.success(_res.message);
            this.uploadLoading = false;
        },

        async uplod_logo(option) { // 上传图片，目前只有一个上传按钮，此方法写死了一个字段，后期上传列多了可以灵活配置
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
            this.shopInfo.shop_logo = _res.data.url;
            this.$antdMessage.success(_res.message);
            this.uploadLoading = false;
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
