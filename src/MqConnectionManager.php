<?php

namespace Mongooer\Conrmq;

use Illuminate\Config\Repository;
use Mongooer\Conrmq\Contracts\MqConnectionInterface;

class MqConnectionManager
{
    protected $config;

    protected $connection = [];

    /**
     * 构造方法
     */
    public function __construct(Repository $config)
    {
        $this->config = $config->get('mongooer_conrmq');
    }

    public function channel(string $channel = "default"): MqConnectionInterface
    {
        if (!isset($this->config["channel"][$channel])) {
            throw new \RuntimeException("channel " . $channel . " is not exists");
        }
        $config = $this->config["channel"][$channel];
//        return new MqConnection($config);
        if (!isset($this->connection[$channel])) {
//
            $this->connection[$channel] = new MqConnection($config);
        }
        return $this->connection[$channel];
    }


    /**
     * 动态将方法传递给默认数据
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->$method(...$parameters);
    }

}
