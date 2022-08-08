<?php

namespace Mongooer\Conrmq\Contracts;


use PhpAmqpLib\Message\AMQPMessage;

interface MqConnectionInterface
{
    public function sendMessage(string $exchange, string $queue, string $routingKey, AMQPMessage $AMQPMessage);

    public function sendJson(string $exchange, string $queue, string $routingKey, string $jsonStr);

    public function listener(string $exchange, string $queue, string $routingKey, \Closure $callback);
}
