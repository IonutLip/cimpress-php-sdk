<?php

namespace Tests\Cimpress;

use Cimpress\Cimpress;
use Cimpress\Services\Auth\CimpressAuthInterface;
use Cimpress\Services\CimpressPrepress;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CimpressTest extends TestCase
{
    /**
     * @test
     */
    public function testCallMustCreateAService()
    {
        // Prepare
        $config = [
            'authVersion' => 'v1',
            'credentials' => [
                'username'   => 'example',
                'password'   => 'example',
                'connection' => 'CimpressADFS',
                'scope'      => 'openid name email',
                'api_type'   => 'app',
            ],
            'cacheType'   => 'database',
            'jwtToken'    => [
                'enableCaching'  => true,
                'tokenTableName' => 'jwt_token',
                'tokenExpireIn'  => 60,
                'databaseUrl'    => 'mysql://user:pass@host/db?password=pass',
            ],
        ];

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAuthenticator = $this->getMockBuilder(CimpressAuthInterface::class)
            ->disableoriginalConstructor()
            ->getMock();

        // Execute
        $cimpress = new Cimpress($config, $mockLogger, $mockAuthenticator);
        $result   = $cimpress->prepress('apikey');

        // Expect
        $this->assertInstanceOf(CimpressPrepress::class, $result);
    }

    /**
     * @test
     */
    public function testConstructorMustAcceptEmptyAuthenticator()
    {
        // Prepare
        $config = [
            'authVersion' => 'v1',
            'credentials' => [
                'username'   => 'example',
                'password'   => 'example',
                'connection' => 'CimpressADFS',
                'scope'      => 'openid name email',
                'api_type'   => 'app',
            ],
            'cacheType'   => 'memory',
        ];

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Execute
        $cimpress = new Cimpress($config, $mockLogger);

        // Expect
        $this->assertInstanceOf(Cimpress::class, $cimpress);
    }
}
