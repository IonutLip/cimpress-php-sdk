<?php

namespace Tests\Cimpress\Services\Auth;

use Cimpress\Services\Auth\CimpressAuthV2;
use Cimpress\Services\Cache\CacheMemory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CimpressAuthV2Test extends TestCase
{
    public function setUp()
    {
        CacheMemory::clear();
    }

    /**
     * @test
     */
    public function testRequestAuthenticated()
    {
        // Prepare
        $history = [];
        $stack   = HandlerStack::create(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "xpto", "token_type": "Bearer", "expires_in": 1000}'), // Request token
                new Response(201, ['Content-Type' => 'application/json'], '{"result": 1}'), // 1st request
                new Response(200, ['Content-Type' => 'application/json'], '{"result": 2}'), // 2nd request
            ])
        );
        $stack->push(Middleware::history($history));

        $mockHttpClient = new Client(['handler' => $stack]);

        $cache = new CacheMemory();

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Execute
        $auth      = new CimpressAuthV2([], $mockHttpClient, $cache, $mockLogger);
        $response1 = $auth->requestAuthenticated(['clientid', 'mysecret'], 'POST', 'http://someurl', ['json' => ['key' => 'value1']]);
        $response2 = $auth->requestAuthenticated(['clientid', 'mysecret'], 'PUT', 'http://otherurl', ['json' => ['key' => 'value2']]);

        // Expect

        // POST to get a token
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[0]['request']->getHeader('Content-Type'));
        parse_str($history[0]['request']->getBody()->getContents(), $params);
        $this->assertEquals(['client_id' => 'clientid', 'client_secret' => 'mysecret', 'grant_type' => 'client_credentials', 'audience' => 'https://api.cimpress.io/'], $params);

        // POST to send 1st request
        $this->assertEquals('POST', $history[1]['request']->getMethod());
        $this->assertEquals(['application/json'], $history[1]['request']->getHeader('Content-Type'));
        $this->assertEquals(['Bearer xpto'], $history[1]['request']->getHeader('Authorization'));
        $this->assertEquals(['key' => 'value1'], json_decode($history[1]['request']->getBody()->getContents(), true));

        // Response of 1st request
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEquals(['result' => 1], json_decode($response1->getBody()->getContents(), true));

        // PUT to send 2nd request
        $this->assertEquals('PUT', $history[2]['request']->getMethod());
        $this->assertEquals(['application/json'], $history[2]['request']->getHeader('Content-Type'));
        $this->assertEquals(['Bearer xpto'], $history[2]['request']->getHeader('Authorization'));
        $this->assertEquals(['key' => 'value2'], json_decode($history[2]['request']->getBody()->getContents(), true));

        // Response of 2nd request
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals(['result' => 2], json_decode($response2->getBody()->getContents(), true));
    }

    /**
     * @test
     */
    public function testRequestAuthenticatedWithExpiredToken()
    {
        // Prepare
        $history = [];
        $stack   = HandlerStack::create(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "invalidtoken", "token_type": "Bearer", "expires_in": 0}'), // Request token
                new Response(401, ['Content-Type' => 'text/plain'], 'Unauthorized'), // 1st request
                new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "validtoken", "token_type": "Bearer", "expires_in": 1000}'), // Request new token
                new Response(201, ['Content-Type' => 'application/json'], '{"result": 1}'), // 1st request retry
            ])
        );
        $stack->push(Middleware::history($history));

        $mockHttpClient = new Client(['handler' => $stack]);

        $cache = new CacheMemory();

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Execute
        $auth     = new CimpressAuthV2([], $mockHttpClient, $cache, $mockLogger);
        $response = $auth->requestAuthenticated(['clientid', 'mysecret'], 'POST', 'http://anotherurl', ['json' => ['key' => 'value']]);

        // Expect

        // POST to get a token
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[0]['request']->getHeader('Content-Type'));
        parse_str($history[0]['request']->getBody()->getContents(), $params);
        $this->assertEquals(['client_id' => 'clientid', 'client_secret' => 'mysecret', 'grant_type' => 'client_credentials', 'audience' => 'https://api.cimpress.io/'], $params);

        // POST to send the request
        $this->assertEquals('POST', $history[1]['request']->getMethod());
        $this->assertEquals(['application/json'], $history[1]['request']->getHeader('Content-Type'));
        $this->assertEquals(['Bearer invalidtoken'], $history[1]['request']->getHeader('Authorization'));
        $this->assertEquals(['key' => 'value'], json_decode($history[1]['request']->getBody()->getContents(), true));

        // POST go get a new token
        $this->assertEquals('POST', $history[2]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[2]['request']->getHeader('Content-Type'));
        parse_str($history[2]['request']->getBody()->getContents(), $params);
        $this->assertEquals(['client_id' => 'clientid', 'client_secret' => 'mysecret', 'grant_type' => 'client_credentials', 'audience' => 'https://api.cimpress.io/'], $params);

        // POST to send a new request
        $this->assertEquals('POST', $history[3]['request']->getMethod());
        $this->assertEquals(['application/json'], $history[3]['request']->getHeader('Content-Type'));
        $this->assertEquals(['Bearer validtoken'], $history[3]['request']->getHeader('Authorization'));
        $this->assertEquals(['key' => 'value'], json_decode($history[3]['request']->getBody()->getContents(), true));

        // Response of request
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['result' => 1], json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @test
     */
    public function testRequestAuthenticatedWithClientErrorResponse()
    {
        // Prepare
        $mockHttpClient = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "xpto", "token_type": "Bearer", "expires_in": 1000}'), // Request token
                    new Response(404, ['Content-Type' => 'application/json'], '{"message": "not found"}'), // 1st request with 4xx error
                ])
            ),
        ]);

        $cache = new CacheMemory();

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Expect
        $this->expectException(RequestException::class);

        // Execute
        $auth = new CimpressAuthV2([], $mockHttpClient, $cache, $mockLogger);
        $auth->requestAuthenticated(['clientid', 'mysecret'], 'POST', 'http://someurl', ['json' => ['key' => 'value']]);
    }

    /**
     * @test
     */
    public function testRequestAuthenticatedWithServerErrorResponse()
    {
        // Prepare
        $mockHttpClient = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(200, ['Content-Type' => 'application/json'], '{"access_token": "xpto", "token_type": "Bearer", "expires_in": 1000}'), // Request token
                    new Response(500, ['Content-Type' => 'application/json'], '{"message": "internal server error"}'), // 1st request with 5xx error
                ])
            ),
        ]);

        $cache = new CacheMemory();

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Expect
        $this->expectException(RequestException::class);

        // Execute
        $auth = new CimpressAuthV2([], $mockHttpClient, $cache, $mockLogger);
        $auth->requestAuthenticated(['clientid', 'mysecret'], 'POST', 'http://someurl', ['json' => ['key' => 'value']]);
    }
}
