<template>
  <a-form-model layout="horizontal" :labelCol="{span: 4}" :wrapper-col="{ span: 14 }">
    <a-form-model-item label="用户UID">
        <a-input v-model="formData.uid" placeholder="请输入UID" />
    </a-form-model-item>
    <a-form-model-item label="积分">
        <a-input v-model="formData.point" placeholder="请输入积分数" />
    </a-form-model-item>
    <a-form-model-item label="加/减">
        <a-select default-value="增加" @change="type_change">
            <a-select-option value="101">
                增加
            </a-select-option>
            <a-select-option value="102">
                减少
            </a-select-option>
        </a-select>
    </a-form-model-item>
    <a-form-model-item :wrapper-col="{ span: 14, offset: 4 }">
      <a-button type="primary"  @click="rewardPoint">确认</a-button>
    </a-form-model-item>
  </a-form-model>
</template>
<script>
import { api_reward_point } from '@/http/reward_point';
export default {
    data() {
        return {
            formData: {
                uid: 0,
                point: 0,
                type: 101,
            },
        };
    },
    computed: {

    },
    methods: {
        type_change(value){
            this.formData.type = value;
        },
        async rewardPoint(){
            const _res = await api_reward_point(this.formData);
            if (_res.result){
                this.$antdMessage.success(_res.message);
            } else {
                this.$antdMessage.error(_res.message);
            }
        }
    }
};
</script>
