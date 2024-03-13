<?php

namespace Shopwwi\LaravelCache;

use Illuminate\Contracts\Redis\Factory;
use support\Redis;

final class RedisFactory implements Factory
{

    public function connection($name = null)
    {
        return Redis::connection($name);
    }
}