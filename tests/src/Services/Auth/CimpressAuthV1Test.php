<?php

namespace Tests\Cimpress\Services\Auth;

use Cimpress\Services\Auth\CimpressAuthV1;
use Cimpress\Services\Cache\CacheMemory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CimpressAuthV1Test extends TestCase
{
    public function setUp(): void
    {
        CacheMemory::clear();
    }

    private function getSampleConfig(): array
    {
        return [
            'username'   => 'sampleuser',
            'password'   => 'samplepass',
            'connection' => 'CimpressADFS',
            'scope'      => 'openid name email',
            'api_type'   => 'app',
        ];
    }

    /**
     * @test
     */
    public function testRequestAuthenticated()
    {
        // Prepare
        $config = $this->getSampleConfig();

        $history = [];
        $stack   = HandlerStack::create(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], '{"id_token": "xpto"}'), // Request token
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
        $auth      = new CimpressAuthV1($config, $mockHttpClient, $cache, $mockLogger);
        $response1 = $auth->requestAuthenticated(['clientid'], 'POST', 'http://someurl', ['json' => ['key' => 'value1']]);
        $response2 = $auth->requestAuthenticated(['clientid'], 'PUT', 'http://otherurl', ['json' => ['key' => 'value2']]);

        // Expect

        // POST to get a token
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[0]['request']->getHeader('Content-Type'));
        parse_str($history[0]['request']->getBody()->getContents(), $params);
        $this->assertEquals(array_merge($config, ['client_id' => 'clientid']), $params);

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
        $config = $this->getSampleConfig();

        $history = [];
        $stack   = HandlerStack::create(
            new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], '{"id_token": "invalidtoken"}'), // Request token
                new Response(401, ['Content-Type' => 'text/plain'], 'Unauthorized'), // 1st request
                new Response(200, ['Content-Type' => 'application/json'], '{"id_token": "validtoken"}'), // Request new token
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
        $auth     = new CimpressAuthV1($config, $mockHttpClient, $cache, $mockLogger);
        $response = $auth->requestAuthenticated(['clientid'], 'POST', 'http://anotherurl', ['json' => ['key' => 'value']]);

        // Expect

        // POST to get a token
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[0]['request']->getHeader('Content-Type'));
        parse_str($history[0]['request']->getBody()->getContents(), $params);
        $this->assertEquals(array_merge($config, ['client_id' => 'clientid']), $params);

        // POST to send the request
        $this->assertEquals('POST', $history[1]['request']->getMethod());
        $this->assertEquals(['application/json'], $history[1]['request']->getHeader('Content-Type'));
        $this->assertEquals(['Bearer invalidtoken'], $history[1]['request']->getHeader('Authorization'));
        $this->assertEquals(['key' => 'value'], json_decode($history[1]['request']->getBody()->getContents(), true));

        // POST go get a new token
        $this->assertEquals('POST', $history[2]['request']->getMethod());
        $this->assertEquals(['application/x-www-form-urlencoded'], $history[2]['request']->getHeader('Content-Type'));
        parse_str($history[2]['request']->getBody()->getContents(), $params);
        $this->assertEquals(array_merge($config, ['client_id' => 'clientid']), $params);

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
        $config = $this->getSampleConfig();

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(200, ['Content-Type' => 'application/json'], '{"id_token": "xpto"}'), // Request token
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
        $auth = new CimpressAuthV1($config, $mockHttpClient, $cache, $mockLogger);
        $auth->requestAuthenticated(['clientid'], 'POST', 'http://someurl', ['json' => ['key' => 'value']]);
    }

    /**
     * @test
     */
    public function testRequestAuthenticatedWithServerErrorResponse()
    {
        // Prepare
        $config = $this->getSampleConfig();

        $mockHttpClient = new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new Response(200, ['Content-Type' => 'application/json'], '{"id_token": "xpto"}'), // Request token
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
        $auth = new CimpressAuthV1($config, $mockHttpClient, $cache, $mockLogger);
        $auth->requestAuthenticated(['clientid'], 'POST', 'http://someurl', ['json' => ['key' => 'value']]);
    }
}
