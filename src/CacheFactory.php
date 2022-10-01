<?php
namespace Shopwwi\LaravelCache;


use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Redis\RedisManager;
use support\Db;

class CacheFactory{


    private $repository;
    private $store = 'file';
    private $config;

    public function __construct()
    {
        $this->config = config('laravelcache');
        $this->store = $this->config['default'];
        $reload = $this->loadStore($this->store);
        $this->repository = new Repository($reload);
    }

    public function store($store)
    {
        $reload = $this->loadStore($store);
        return new Repository($reload);
    }

    protected function loadStore($store){
        $prefix = $this->config['prefix'];
        if($store ==='redis'){
            $config = \config('redis');
            $client = $config['client'] ?? 'phpredis';
            $redisConfig = $this->config['stores']['redis'];
            $connection = $redisConfig['connection'] ?? 'default';
            $reStore = new RedisStore(new RedisManager('', $client, $config),$prefix,$connection);
            $reStore->setLockConnection($redisConfig['lock_connection'] ?? $connection);
        }elseif($store ==='memcached'){
            $config = $this->config['stores']['memcached'];
            $memcached = (new \Illuminate\Cache\MemcachedConnector)->connect(
                $config['servers'],
                $config['persistent_id'] ?? null,
                $config['options'] ?? [],
                array_filter($config['sasl'] ?? [])
            );
            $reStore = new MemcachedStore($memcached, $prefix);
        }elseif($store ==='database'){
            $config = $this->config['stores']['database'];
            $connection = Db::connection($config['connection'] ?? null);
            $reStore = new DatabaseStore(
                $connection,
                $config['table'],
                $prefix,
                $config['lock_table'] ?? 'cache_locks',
                $config['lock_lottery'] ?? [2, 100]
            );
            $reStore->setLockConnection(Db::connection($config['lock_connection'] ?? $config['connection'] ?? null));
        }else{
            $reStore = new FileStore(new Filesystem(),$this->config['stores']['file']['path']);
        }
        return $reStore;
    }

    public function __call(string $method, array $args)
    {
        return call_user_func_array([$this->repository, $method], $args);
    }
}