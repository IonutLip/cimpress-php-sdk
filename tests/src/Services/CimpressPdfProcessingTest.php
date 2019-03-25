<?php

namespace Tests\Cimpress\Services;

use Cimpress\Services\Auth\CimpressAuthInterface;
use Cimpress\Services\CimpressPdfProcessing;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CimpressPdfProcessingTest extends TestCase
{
    /**
     * @test
     */
    public function testMergePages()
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
                'https://pdf.prepress.documents.cimpress.io/v2/mergePages?asynchronous=true',
                [
                    'json' => [
                        'PdfUrls'       => ['http://pdf1.example', 'http://pdf2.example'],
                        'CallbackUrl'   => 'http://callback.example',
                    ],
                ]
            )
            ->will($this->returnValue(
                new Response(200, ['Content-Type' => 'application/json'], '{"result": true}')
            ));

        // Execute
        $service = new CimpressPdfProcessing($credentials, $mockAuthenticator, $mockLogger);
        $result   = $service->mergePages(['http://pdf1.example', 'http://pdf2.example'], 'http://callback.example');

        // Expect
        $this->assertEquals(['result' => true], $result);
    }
}
