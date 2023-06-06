<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "global_setting".
 *
 * @property int $id
 * @property string $key 键
 * @property string $value 值
 */
class GlobalSetting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'global_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['value'], 'required'],
            [['key'], 'string', 'max' => 50],
            [['value'], 'string', 'max' => 2000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Key',
            'value' => 'Value',
        ];
    }
}
