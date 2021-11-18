<?php

namespace Mongooer\Conrmq\Contracts;

interface MqConnectionInterface
{
    public function setExChange(string $exChangeName): self;

    public function setQueue(string $queueName): self;

    public function publisherArray(array $messageArray);

    public function publisherString(string $messageBody);

    public function listener(\Closure $callback);

    public function reconnect();
}
