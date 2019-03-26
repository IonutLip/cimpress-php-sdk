<?php

namespace Tests\Cimpress\Services\Cache;

use Cimpress\Entity\AuthToken;
use Cimpress\Services\Cache\CacheMemory;
use PHPUnit\Framework\TestCase;

class CacheMemoryTest extends TestCase
{
    private static $config = [
        'enableCaching' => true,
    ];

    public function setUp()
    {
        CacheMemory::clear();
    }

    /**
     * @test
     */
    public function testBasicStoreExistsAndFetch()
    {
        // Prepare
        $key   = 'foo';
        $value = new AuthToken('Bearer', 'bar', 10);

        // Execute
        $cache = new CacheMemory(static::$config);
        $cache->store($key, $value, 10);
        $fooExists   = $cache->exists('foo');
        $barExists   = $cache->exists('bar');
        $resultFetch = $cache->fetch($key);

        // Expect
        $this->assertTrue($fooExists);
        $this->assertFalse($barExists);
        $this->assertEquals($value, $resultFetch);
    }

    /**
     * @test
     */
    public function testClear()
    {
        // Prepare
        $cache = new CacheMemory(static::$config);
        $cache->store('foo', 'bar', 10);

        // Execute
        CacheMemory::clear();
        $fooExists   = $cache->exists('foo');
        $resultFetch = $cache->fetch('foo');

        // Expect
        $this->assertFalse($fooExists);
        $this->assertNull($resultFetch);
    }

    /**
     * @test
     */
    public function testTtl()
    {
        // Prepare
        $cache = new CacheMemory(static::$config);
        $cache->store('foo', 'bar', 1);

        // Execute
        sleep(2);
        $fooExists   = $cache->exists('foo');
        $resultFetch = $cache->fetch('foo');

        // Expect
        $this->assertFalse($fooExists);
        $this->assertNull($resultFetch);
    }
}
