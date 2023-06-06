<?php
/**
 * elasticsearch搜索实例
 * @author xudt
 * @date   : 2020/3/8 14:34
 */
namespace common\components;

use Elasticsearch\ClientBuilder;
use yii\base\Component;

class ElasticSearch extends Component
{
    public $hosts;

    protected $instance;

    public function init()
    {
        $this->instance = ClientBuilder::create()->setHosts($this->hosts)->build();
    }

    public function getClient()
    {
        return $this->instance;
    }
}