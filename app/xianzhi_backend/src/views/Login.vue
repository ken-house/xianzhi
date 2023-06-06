<template>
  <a-form-model layout="horizontal" :labelCol="{span: 4}" :wrapper-col="{ span: 14 }">
    <a-form-model-item label="用户名">
      <a-input v-model="formData.username" placeholder="请输入用户名" autocomplete="off"/>
    </a-form-model-item>
    <a-form-model-item label="密码">
      <a-input v-model="formData.password" placeholder="请输入密码" type="password" autocomplete="off"/>
    </a-form-model-item>
    <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
      <a-button type="primary"  @click="userLogin">登录</a-button>
    </a-form-model-item>
  </a-form-model>
</template>
<script>
import { api_login } from '@/http/login';
export default {
    data() {
        return {
            formData: {
                username: '',
                password: '',
            },
        };
    },
    computed: {

    },
    methods: {
        async userLogin(){
            const _res = await api_login(this.formData);
            if (_res.result){
                this.$router.replace({
                    path: '/product-audit'
                });
            } else {
                this.$antdMessage.error(_res.message);
            }
        }
    }
};
</script>
