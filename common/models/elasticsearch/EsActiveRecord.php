<?php
/**
 * 封装一个ElasticSearch基础类
 *
 * @author xudt
 * @date   : 2020/3/8 15:10
 */

namespace common\models\elasticsearch;

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use Yii;

class EsActiveRecord
{
    const PAGESIZE = 10; //搜索分页数

    protected static $key = 'es'; //使用的ES名称

    /**
     * 索引
     *
     * @return mixed
     * @author   xudt
     * @dateTime 2020/3/8 15:14
     */
    public static function index()
    {
        return Inflector::pluralize(Inflector::camel2id(StringHelper::basename(get_called_class()), '-'));
    }

    /**
     * 字段映射mapping
     *
     * @return mixed
     * @author   xudt
     * @dateTime 2020/3/8 15:15
     */
    public static function mapping()
    {
        return Inflector::camel2id(StringHelper::basename(get_called_class()), '-');
    }


    /**
     * 按id查找一条数据
     *
     * @param       $primaryKey
     *
     * @return null
     * @author   xudt
     * @dateTime 2020/3/8 15:17
     */
    public static function get($primaryKey)
    {
        if ($primaryKey === null) {
            return null;
        }
        $params = [
            'index' => static::index(),
            'id' => $primaryKey,
        ];
        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->get($params);
            if ($response['found']) {
                return !empty($response['_source']) ? $response['_source'] : null;
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * 按id查找多条数据
     *
     * @param array $primaryKeys
     *
     * @return array|null
     * @author   xudt
     * @dateTime 2020/3/8 15:20
     */
    public static function mget(array $primaryKeys)
    {
        if (empty($primaryKeys)) {
            return [];
        }

        $params = [
            'index' => static::index(),
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();

        if (count($primaryKeys) === 1) {
            $params['id'] = reset($primaryKeys);
            try {
                $response = $client->get($params);
                if ($response['found']) {
                    return !empty($response['_source']) ? [$response['_source']] : null;
                }
            } catch (\Exception $e) {
                //handle error here
            }
        }

        $models = [];
        foreach ($primaryKeys as $pri) {
            $params['id'] = $pri;
            try {
                $response = $client->get($params);
                if ($response['found']) {
                    $models[] = !empty($response['_source']) ? $response['_source'] : null;
                }
            } catch (\Exception $e) {
                //handle error here
            }
        }

        return $models;
    }

    /**
     * 更新一条数据
     *
     * @param       $primaryKey
     * @param array $options
     *
     * @return bool|null
     * @author   xudt
     * @dateTime 2020/3/8 15:21
     */
    public static function update($primaryKey, $options = [])
    {
        if ($primaryKey === null) {
            return null;
        }
        $params = [
            'index' => static::index(),
            'id' => $primaryKey,
            'body' => [
                'doc' => $options
            ]
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->update($params);
        if ($response['_shards']) {
            return !empty($response['_shards']['successful']) ? true : false;
        }

        return false;
    }

    /**
     * 根据查询条件批量更新数据
     *
     * @param       $primaryKey
     * @param array $options
     *
     * @return bool|null
     * @author   xudt
     * @dateTime 2020/3/8 15:21
     */
    public static function bulkUpdate(array $options)
    {
        $params = [
            'index' => static::index(),
            'body' => $options
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->updateByQuery($params);
        if ($response['total'] == $response['updated']) {
            return true;
        }

        return false;
    }

    /**
     * 创建一条数据.
     *
     * @param null  $primaryKey
     * @param array $options
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:21
     */
    public static function insert($primaryKey = null, $options = [])
    {
        $params = [
            'index' => static::index(),
            'body' => $options
        ];

        if ($primaryKey != null) {
            $params['id'] = $primaryKey;
        }

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->index($params);
        if ($response['_shards']) {
            return !empty($response['_shards']['successful']) ? true : false;
        }

        return false;
    }

    /**
     * 删除一条数据.
     *
     * @param $primaryKey
     *
     * @return bool|null
     * @author   xudt
     * @dateTime 2020/3/8 15:21
     */
    public static function delete($primaryKey)
    {
        if ($primaryKey === null) {
            return null;
        }

        $record = self::get($primaryKey);
        if (!$record) {
            return false;
        }

        $params = [
            'index' => static::index(),
            'id' => $primaryKey
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->delete($params);
        if ($response['_shards']) {
            return !empty($response['_shards']['successful']) ? true : false;
        }

        return false;
    }

    /**
     * 获取分词效果
     *
     * @param $wd
     * @param $analyzer
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:22
     */
    public static function analyze($wd, $analyzer)
    {
        $params = [
            'index' => static::index(),
            'body' => [
                'text' => $wd,
                'analyzer' => $analyzer,
            ],
        ];
        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->analyze($params);
        } catch (\Exception $e) {
            var_export($e->getMessage());
            return false;
        }
        return $response;
    }


    /**
     * 创建一个索引
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:22
     */
    public static function createIndex()
    {
        $params = [
            'index' => static::index(),
            'body' => [
                'settings' =>
                    [
                        /*** 此参数增大是为了 减少加入许多document时es服务的压力 ***/
                        'number_of_shards' => 1,
                        'index' => ['refresh_interval' => '5s'],
                        'analysis' => [
                            "analyzer" => [
                                'ik' => [
                                    'tokenizer' => 'ik_max_word',
                                ]
                            ]
                        ]
                    ],
                'mappings' => static::mapping()
            ]
        ];


        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->create($params);
        } catch (\Exception $e) {
            var_export($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 更新索引
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:22
     */
    public static function updateIndex()
    {
        $params = [
            'index' => static::index(),
            'body' => [
                'settings' =>
                    [
                        /*** 此参数增大是为了 减少加入许多document时es服务的压力 ***/
                        'index' => ['refresh_interval' => '5s'],
                        'analysis' => [
                            'analyzer' => [
                                'ik_pinyin_analyzer' =>
                                    [
                                        'type' => 'custom',
                                        'tokenizer' => 'ik_smart',
                                        'filter' => ['my_pinyin']
                                    ]
                            ],
                            'filter' => [
                                'my_pinyin' =>
                                    [
                                        'type' => 'pinyin',
                                        'keep_first_letter' => false,
                                        'remove_duplicated_term' => true,   //删除重复的词条
                                        'keep_none_chinese_in_first_letter' => false
                                    ]
                            ]
                        ]
                    ],
            ]
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->putSettings($params);
            var_export($response);
        } catch (\Exception $e) {
            var_export($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 删除索引
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:23
     */
    public static function deleteIndex()
    {
        $params = [
            'index' => static::index(),
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->delete($params);
        } catch (\Exception $e) {
            //handle error here
            var_export($e->getMessage());
            return false;
        }

        return $response;
    }

    /**
     * 更新mapping
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:23
     */
    public static function updateMapping()
    {
        $params = [
            'index' => static::index(),
            'body' => [
                'mappings' => static::mapping()
            ]
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->putMapping($params);
        } catch (\Exception $e) {
            //handle error here
            return false;
        }

        return true;
    }

    /**
     * 获取mapping
     *
     * @return bool
     * @author   xudt
     * @dateTime 2020/3/8 15:23
     */
    public static function getMapping()
    {
        $params = [
            'index' => static::index(),
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        try {
            $response = $client->indices()->getMapping($params);
        } catch (\Exception $e) {
            var_export($e->getMessage());
            //handle error here
            return false;
        }

        return $response;
    }

    /**
     * 搜索
     *
     * @param array  $options
     * @param string $page
     * @param string $pageSize
     *
     * @return null
     * @author   xudt
     * @dateTime 2020/3/8 15:24
     */
    public static function search($options = [], $page = '', $pageSize = '')
    {
        if (empty($options)) {
            return null;
        }

        $params = [
            'index' => static::index(),
            'body' => $options
        ];

        if ($page) {
            $pageSize = $pageSize ? $pageSize : self::PAGESIZE;
            $params['body']['from'] = ($page - 1) * $pageSize;
            $params['body']['size'] = $pageSize;
        }

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->search($params);

        return $response;
    }

    /**
     * 通过body删除数据
     *
     * @param $body
     *
     * @return int
     * @author   xudt
     * @dateTime 2020/3/8 15:25
     */
    public static function deleteByQuery($body)
    {
        $options = [
            'index' => static::index(),
            'body' => $body,
        ];

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->deleteByQuery($options);
        //返回删除的行数
        if (isset($response['deleted'])) {
            return !empty($response['deleted']) ? $response['deleted'] : 0;
        }
        return 0;
    }

    /**
     * 批量写入
     *
     * @param array  $options
     * @param string $idField 是否指定id
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * @author     xudt
     * @date-time  2021/4/19 09:42
     */
    public static function bulk($options = [], $idField = '')
    {
        $params = ['body' => []];
        foreach ($options as $key => $doc) {
            $docIndex = [
                'index' => [
                    '_index' => static::index(),
                ],
            ];
            if(!empty($idField)){
                $docIndex['index']['_id'] = $doc[$idField];
            }
            $params['body'][] = $docIndex;
            $params['body'][] = $doc;
        }

        /** @var Client $client */
        $client = Yii::$app->get(static::$key)->getClient();
        $response = $client->bulk($params);
        unset($response);
        return true;
    }
}