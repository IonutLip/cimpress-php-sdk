<?php

namespace Tests\Cimpress\Services\Cache;

use Cimpress\Services\Cache\CacheRedis;
use PHPUnit\Framework\TestCase;

class CacheRedisTest extends TestCase
{
    public static $config = [
        'enableCaching' => true,
        'host'          => 'localhost',
        'port'          => 6379,
        'database'      => 5,
    ];

    public function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped(sprintf('%s requires redis extension', __CLASS__));
        }
        $r = new \Redis();
        if (!$r->connect('localhost', 6379)) {
            $this->markTestSkipped(sprintf('%s requires redis server runnin on localhost:6379', __CLASS__));
        }
    }

    /**
     * @test
     */
    public function testBasicStoreExistsAndFetch()
    {
        // Prepare
        $key   = 'foo';
        $value = 'bar';

        // Execute
        $cache = new CacheRedis(static::$config);
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
        $cache = new CacheRedis(static::$config);
        $cache->store('foo', 'bar', 10);

        // Execute
        $cache->clear();
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
        $cache = new CacheRedis(static::$config);
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
