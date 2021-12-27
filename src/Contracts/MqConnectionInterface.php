<?php

namespace Mongooer\Conrmq\Contracts;

use PhpAmqpLib\Message\AMQPMessage;

interface MqConnectionInterface
{
    public function setExChange(string $exChangeName): self;

    public function setQueue(string $queueName): self;

    public function setRoutingKey(string $routingKey): self;

    public function publisherJson(string $jsonStr);

    public function publisherMessage(AMQPMessage $message);

    public function listener(\Closure $callback);

    public function reconnect();
}
