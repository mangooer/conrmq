<?php

namespace Mongooer\Conrmq\Facades;

use Illuminate\Support\Facades\Facade;
use Mongooer\Conrmq\Contracts\MqConnectionInterface;

/**
 * @method static \Mongooer\Conrmq\Contracts\MqConnectionInterface channel(string $channel = "default")
 *
 */
class MongooerConMq extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'mongooerConMq';
    }
}
