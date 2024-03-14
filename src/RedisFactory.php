<?php

namespace Shopwwi\LaravelCache;

use Illuminate\Contracts\Redis\Factory;
use support\Redis;

final class RedisFactory implements Factory
{

    public function connection($name = null)
    {
        if($name == null){
            $name = 'default';
        }
        return Redis::connection($name);
    }

    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}