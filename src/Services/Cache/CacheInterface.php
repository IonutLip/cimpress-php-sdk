<?php

namespace Cimpress\Services\Cache;

interface CacheInterface
{
    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = []);

    /**
     * Fetch a entry by its id
     *
     * @param string $id
     * @return mixed
     */
    public function fetch(string $id);

    /**
     * Check whether a entry exist in cache by its id
     *
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool;

    /**
     * Store a new value in the cache using an id
     *
     * @param string $id
     * @param mixed $value
     * @param int $ttl Time to live (use 0 to not expire the data)
     * @return void
     */
    public function store(string $id, $value, int $ttl = 0);

    /**
     * Check whether the cache is enabled or not
     *
     * @return bool
     */
    public function isCachingEnabled(): bool;
}
