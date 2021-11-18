<?php

namespace Mongooer\Conrmq\Facades;

use Illuminate\Support\Facades\Facade;
use Mongooer\Conrmq\Contracts\MqConnectionInterface;

class MongooerConMq extends Facade
{
    /**
     * @method static \Mongooer\Conrmq\Contracts\MqConnectionInterface driver(string $driver = "default")
     *
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mongooerConMq';
    }
}
