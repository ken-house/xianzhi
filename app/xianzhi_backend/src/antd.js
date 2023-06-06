import Vue from 'vue';
import {
    Icon,
    Layout,
    Menu,
    Breadcrumb,
    DatePicker,
    FormModel,
    Select,
    Input,
    Button,
    message,
    Table,
    Descriptions,
    PageHeader,
    Tabs,
    Spin,
    ConfigProvider,
    Row,
    Col,
    Modal,
    Radio,
    Checkbox,
    Cascader,
    Tag,
    Popover,
    Result,
    Switch,
    Divider
} from 'ant-design-vue';

Vue.use(ConfigProvider);
Vue.use(Icon);
Vue.use(Layout);
Vue.use(Menu);
Vue.use(Breadcrumb);
Vue.use(DatePicker);
Vue.use(FormModel);
Vue.use(Select);
Vue.use(Input);
Vue.use(Button);
Vue.use(Table);
Vue.use(Descriptions);
Vue.use(PageHeader);
Vue.use(Tabs);
Vue.use(Spin);
Vue.use(Row);
Vue.use(Col);
Vue.use(Modal);
Vue.use(Radio);
Vue.use(Checkbox);
Vue.use(Cascader);
Vue.use(Tag);
Vue.use(Popover);
Vue.use(Result);
Vue.use(Switch);
Vue.use(Divider);
Vue.prototype.$message = message;
