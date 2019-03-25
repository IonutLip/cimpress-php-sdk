<?php

namespace Cimpress\Services\Cache;

class CacheMemory implements CacheInterface
{
    /**
     * Array where the data is stored (same array for all cache instances)
     *
     * @var array
     */
    private static $data = [];

    /** @var array */
    private $config;

    /**
     * {@inheritdoc}
     *
     * @param array $config Array with:
     *     bool 'enableCaching'
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
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
    public static function clear(): void
    {
        static::$data = [];
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
        return static::$data[$id]['value'];
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
        if (!isset(static::$data[$id])) {
            return false;
        }
        $expire = static::$data[$id]['expire'];
        if ($expire && $expire < time()) {
            unset(static::$data[$id]);
            return false;
        }
        return true;
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

        static::$data[$id] = [
            'expire' => $ttl ? time() + $ttl : 0,
            'value'  => $value,
        ];
    }
}
