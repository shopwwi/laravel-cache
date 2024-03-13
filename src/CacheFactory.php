<?php
namespace Shopwwi\LaravelCache;


use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Redis\RedisManager;
use support\Db;
use support\Redis;

class CacheFactory{

    protected $config;
    /**
     * The array of resolved cache stores.
     *
     * @var array
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    public function __construct()
    {
        $this->config = config('laravelcache');
    }
    /**
     * Get a cache store instance by name, wrapped in a repository.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();
        return $this->stores[$name] = $this->getStoreName($name);
    }

    /**
     * Get a cache driver instance.
     *
     * @param  string|null  $driver
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function driver($driver = null)
    {
        return $this->store($driver);
    }

    /**
     * Attempt to get the store from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getStoreName($name)
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }

    /**
     * Get the cache connection configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->config['stores'][$name];
        }
        return ['driver' => 'null'];
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('laravelcache.default');
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new \Exception("Cache store [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($config);
            } else {
                throw new \Exception("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Unset the given driver instances.
     *
     * @param  array|string|null  $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->stores[$cacheName])) {
                unset($this->stores[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Disconnect the given driver and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name ??= $this->getDefaultDriver();

        unset($this->stores[$name]);
    }

    protected function loadStore($store){
        $prefix = $this->config['prefix'];
        if($store ==='redis'){
            $config = \config('redis');
            $client = $config['client'] ?? 'phpredis';
            $redisConfig = $this->config['stores']['redis'];
            $connection = $redisConfig['connection'] ?? 'default';
            $reStore = new RedisStore(new RedisFactory(),$prefix,$connection);
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

    /**
     * Create an instance of the APC cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createApcDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        return $this->repository(new ApcStore(new ApcWrapper, $prefix));
    }

    /**
     * Create an instance of the array cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createArrayDriver(array $config)
    {
        return $this->repository(new ArrayStore($config['serialize'] ?? false));
    }

    /**
     * Create an instance of the file cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(new FileStore(new Filesystem(), $config['path'], $config['permission'] ?? null));
    }

    /**
     * Create an instance of the Memcached cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createMemcachedDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        $memcached = (new \Illuminate\Cache\MemcachedConnector)->connect(
            $config['servers'],
            $config['persistent_id'] ?? null,
            $config['options'] ?? [],
            array_filter($config['sasl'] ?? [])
        );

        return $this->repository(new MemcachedStore($memcached, $prefix));
    }

    /**
     * Create an instance of the Null cache driver.
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function createNullDriver()
    {
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createRedisDriver(array $config)
    {
        $connection = $config['connection'] ?? 'default';
        $redisConfig = \config('redis');
        $client = $redisConfig['client'] ?? 'phpredis';

        $store = new RedisStore(new RedisManager('', $client, $redisConfig), $this->getPrefix($config), $connection);

        return $this->repository(
            $store->setLockConnection($config['lock_connection'] ?? $connection)
        );
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createDatabaseDriver(array $config)
    {
        $connection = Db::connection($config['connection'] ?? null);

        $store = new DatabaseStore(
            $connection,
            $config['table'],
            $this->getPrefix($config),
            $config['lock_table'] ?? 'cache_locks',
            $config['lock_lottery'] ?? [2, 100]
        );

        return $this->repository($store->setLockConnection(
            Db::connection($config['lock_connection'] ?? $config['connection'] ?? null)
        ));
    }

    /**
     * Get the cache prefix.
     *
     * @param  array  $config
     * @return string
     */
    protected function getPrefix(array $config)
    {
        return $config['prefix'] ?? $this->config['prefix'];
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this, $config);
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return \Illuminate\Cache\Repository
     */
    public function repository(Store $store)
    {
        return new Repository($store);
    }

    /**
     * Dynamically call the default driver instance.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}