// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import store from '@/store';
import Moment from 'moment';
import { Modal, message, ConfigProvider } from 'ant-design-vue';
import { VueEditor } from "vue2-editor";
import './antd';
import 'ant-design-vue/dist/antd.css';
import '@/assets/style/global.css';

Vue.config.productionTip = false

Vue.prototype.$antdModal = Modal; //  对话框
Vue.prototype.$antdMessage = message; //  提示
Vue.prototype.$antdMoment = Moment; //  时间格式化

// ant Design 国际化配置
Vue.use(ConfigProvider);
Vue.use(VueEditor);

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  store,
  components: { App },
  template: '<App/>'
})
