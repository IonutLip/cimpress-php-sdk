<?php

namespace Tests\Cimpress\Services\Cache;

use Cimpress\Entity\AuthToken;
use Cimpress\Services\Cache\CacheDatabase;
use PHPUnit\Framework\TestCase;

class CacheDatabaseTest extends TestCase
{
    public static $config = [
        'enableCaching'  => true,
        'tokenTableName' => 'jwt_token',
        'databaseDsn'    => 'sqlite::memory:',
    ];

    public function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped(sprintf('%s requires sqlite support', __CLASS__));
        }
    }

    /**
     * @test
     */
    public function testBasicStoreExistsAndFetch()
    {
        // Prepare
        $key   = 'foo';
        $ttl   = 10;
        $value = new AuthToken('Bearer', $this->buildJWT('bar', $ttl), $ttl);

        // Execute
        $cache = new CacheDatabase(static::$config);
        $cache->store($key, $value, $ttl);
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
    public function testTtl()
    {
        // Prepare
        $key   = 'foo';
        $ttl   = 1;
        $value = new AuthToken('Bearer', $this->buildJWT('bar', $ttl), $ttl);

        $cache = new CacheDatabase(static::$config);
        $cache->store($key, $value, $ttl);

        // Execute
        sleep(2);
        $fooExists   = $cache->exists('foo');
        $resultFetch = $cache->fetch('foo');

        // Expect
        $this->assertFalse($fooExists);
        $this->assertNull($resultFetch);
    }

    private function buildJWT($value, $ttl): string
    {
        $header = base64_encode(
            json_encode(['alg' => 'HS256', 'typ' => 'JWT'])
        );
        $payload = base64_encode(
            json_encode(['iss' => 'localhost', 'exp' => strtotime(sprintf('now +%d seconds', $ttl))])
        );
        $signature = base64_encode(
            hash_hmac('sha256', "$header.$payload", 'pass', true)
        );

        return implode('.', [$header, $payload, $signature]);
    }
}
