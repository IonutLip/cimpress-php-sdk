<?php

namespace Cimpress\Services\Cache;

class CacheRedis implements CacheInterface
{
    /** @var array */
    private $config;

    /** @var \Redis */
    private $redis;

    /**
     * {@inheritdoc}
     *
     * @param array $config Array with:
     *     bool 'enableCaching'
     *     string  'host' - Redis host. I.E. '127.0.0.1'
     *     int 'port' - Optional port. I.E. 6379
     *     string 'database' - Redis database I.E. '5'
     *     string 'password' - Optional redis password
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->redis  = null;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isCachingEnabled(): bool
    {
        return (bool) ($this->config['enableCaching'] ?? false);
    }

    /**
     * Clear all cache entries
     *
     * @return void
     */
    public function clear(): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }
        $this->getRedis()->flushDb();
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     * @return mixed
     */
    public function fetch(string $id)
    {
        if (!$this->exists($id)) {
            return null;
        }
        try {
            return $this->getRedis()->get($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        try {
            return $this->getRedis()->exists($id) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id
     * @param mixed $value
     * @param int $ttl Time to live (use 0 to not expire the data)
     * @return void
     */
    public function store(string $id, $value, int $ttl = 0): void
    {
        if (!$this->isCachingEnabled()) {
            return;
        }

        try {
            if ($ttl > 0) {
                $this->getRedis()->setex($id, (int) $ttl, $value);
                return;
            }
            $this->getRedis()->set($id, $value);
        } catch (\Exception $e) {
            // ignore
        }
    }

    /**
     * Get a redis instance, already connected/authenticated to configured database.
     * If the connection is lost, it reconnect automatically.
     *
     * @return \Redis
     */
    private function getRedis(): \Redis
    {
        if ($this->redis) {
            try {
                $this->redis->ping();
            } catch (\RedisException $e) {
                $this->connect();
            }

            return $this->redis;
        }

        $this->connect();
        return $this->redis;
    }

    /**
     * Connect to the configured redis and updates #redis attribute
     *
     * @return void
     */
    private function connect(): void
    {
        $redis = new \Redis();

        $params = [$this->config['host']];
        if (isset($this->config['port'])) {
            $params[] = $this->config['port'];
        }
        if (!$redis->connect(...$params)) {
            throw new \Exception('Failed to connect to redis');
        }
        if (isset($this->config['password']) && !$redis->auth($this->config['password'])) {
            throw new \Exception('Failed to authenticate to redis');
        }
        if (!$redis->select($this->config['database'])) {
            throw new \Exception('Failed to select database in redis');
        }
        $this->redis = $redis;
    }
}
