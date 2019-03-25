<?php

namespace Tests\Cimpress\Entity;

use Cimpress\Entity\AuthToken;
use PHPUnit\Framework\TestCase;

class AuthTokenTest extends TestCase
{
    /**
     * @test
     */
    public function testConstructor()
    {
        // Prepare
        $tokenType   = 'foo';
        $accessToken = 'bar';
        $ttl         = 10;

        // Execute
        $t = new AuthToken($tokenType, $accessToken, $ttl);

        // Expect
        $this->assertEquals($accessToken, $t->accessToken);
        $this->assertEquals($tokenType, $t->tokenType);
        $this->assertEquals($ttl, $t->ttl);
    }
}
