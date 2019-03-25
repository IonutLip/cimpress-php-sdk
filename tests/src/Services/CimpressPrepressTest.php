<?php

namespace Tests\Cimpress\Services;

use Cimpress\Services\Auth\CimpressAuthInterface;
use Cimpress\Services\CimpressPrepress;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CimpressPrepressTest extends TestCase
{
    /**
     * @test
     */
    public function testFilePrep()
    {
        // Prepare
        $credentials = ['xpto'];

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAuthenticator = $this->getMockBuilder(CimpressAuthInterface::class)
            ->disableoriginalConstructor()
            ->getMock();

        $mockAuthenticator
            ->method('requestAuthenticated')
            ->with(
                ['xpto'],
                'POST',
                'https://prepress.documents.cimpress.io/v2/file-prep?asynchronous=true',
                [
                    'json' => [
                        'FileUrl'       => 'http://file.example',
                        'ParametersUrl' => 'http://param.example',
                        'CallbackUrl'   => 'http://callback.example',
                    ],
                ]
            )
            ->will($this->returnValue(
                new Response(200, ['Content-Type' => 'application/json'], '{"result": true}')
            ));

        // Execute
        $prepress = new CimpressPrepress($credentials, $mockAuthenticator, $mockLogger);
        $result   = $prepress->filePrep('http://file.example', 'http://param.example', 'http://callback.example');

        // Expect
        $this->assertEquals(['result' => true], $result);
    }

    /**
     * @test
     */
    public function testPrintPrep()
    {
        // Prepare
        $credentials = ['xpto', 'otpx'];

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAuthenticator = $this->getMockBuilder(CimpressAuthInterface::class)
            ->disableoriginalConstructor()
            ->getMock();

        $mockAuthenticator
            ->method('requestAuthenticated')
            ->with(
                ['xpto', 'otpx'],
                'POST',
                'https://prepress.documents.cimpress.io/v2/print-prep?inline=true&noRedirect=true&asynchronous=true',
                [
                    'json' => [
                        'DocumentInstructionsUrl' => 'http://instruction.example',
                        'ParametersUrl'           => 'http://param.example',
                    ],
                ]
            )
            ->will($this->returnValue(
                new Response(200, ['Content-Type' => 'application/json'], '{"result": true}')
            ));

        // Execute
        $prepress = new CimpressPrepress($credentials, $mockAuthenticator, $mockLogger);
        $result   = $prepress->printPrep('http://instruction.example', 'http://param.example');

        // Expect
        $this->assertEquals(['result' => true], $result);
    }
}
