<?php

namespace Cimpress\Services;

use Cimpress\Services\Auth\CimpressAuthInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class BaseCimpress
 */
class BaseCimpress implements CimpressServiceInterface
{
    /**
     * Array with credentials, according to the version of authentication
     *
     * @var array $credentials
     */
    private $credentials;

    /**
     * Authenticator object that authenticates the API and calls an end-point
     *
     * @var CimpressAuthInterface $authenticator Authenticator
     */
    private $authenticator;

    /** @var Loggerinterface $logger Logger */
    private $logger;

    /**
     * BaseCimpress constructor.
     *
     * @param array $credentials
     * @param CimpressAuthInterface $authenticator
     * @param LoggerInterface $logger
     */
    public function __construct(array $credentials, CimpressAuthInterface $authenticator, LoggerInterface $logger)
    {
        $this->credentials   = $credentials;
        $this->authenticator = $authenticator;
        $this->logger        = $logger;
    }

    /**
     * Make authenticated request to cimpress (using credentials defined in constructor)
     * and returns parsed JSON data as associative array.
     *
     * @param string $method HTTP method
     * @param string $url
     * @param array $options Guzzle options
     * @return array
     * @throws \Throwable
     */
    protected function requestJson(string $method, string $url, array $options = []): array
    {
        $context = [
            'url'     => $url,
            'method'  => $method,
            'request' => $options,
        ];
        try {
            $response = $this->authenticator->requestAuthenticated($this->credentials, $method, $url, $options);
            $message  = 'Cimpress request successfully';
            $body     = $response->getBody()->getContents();
            $data     = json_decode($body, true);

            $context['response']    = $body;
            $context['status_code'] = $response->getStatusCode();

            $this->logger->info($message, $context);
        } catch (RequestException $exc) {
            $context['exception_type'] = get_class($exc);
            if ($exc->hasResponse()) {
                $context['response']    = $exc->getResponse()->getBody()->getContents();
                $context['status_code'] = $exc->getResponse()->getStatusCode();
            }
            $this->logger->error('Cimpress request exception', $context);
            throw $exc;

        } catch (\Throwable $exc) {
            $context['exception_type']    = get_class($exc);
            $context['exception_message'] = $exc->getMessage();
            $this->logger->error('Cimpress request exception', $context);
            throw $exc;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(sprintf(
                'Failed to decode JSON data (raw string = %s / json error = %s %s)',
                var_export($body, true),
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $data;
    }
}
