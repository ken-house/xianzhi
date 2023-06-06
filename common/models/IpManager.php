<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ip_manager".
 *
 * @property int $id
 * @property int $count 出现次数
 * @property string $ip ip地址
 * @property int $status 0 未屏蔽 1已屏蔽
 * @property int $updated_at 更新时间
 */
class IpManager extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ip_manager';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['count', 'status', 'updated_at'], 'integer'],
            [['ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'count' => 'Count',
            'ip' => 'Ip',
            'status' => 'Status',
            'updated_at' => 'Updated At',
        ];
    }
}
