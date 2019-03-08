<?php

namespace Cimpress\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class BaseCimpress
 */
class BaseCimpress
{
    /** @var string $token Authorize Token */
    private $token;

    /** @var string $logger Logger */
    private $logger;

    /**
     * BaseCimpress constructor.
     *
     * @param string $token
     * @param LoggerInterface $logger
     */
    public function __construct(string $token, LoggerInterface $logger)
    {
        $this->token = $token;
        $this->logger = $logger;
    }

    /**
     * Make request to cimpress
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @return array
     * @throws \Throwable
     */
    protected function requestJson(string $url, array $params, string $method = 'POST'): array
    {
        $options = [
            'headers' => [ 'Authorization' => $this->getToken(true) ],
            'json' => $params
        ];

        $context = [
            'url' => $url,
            'method' => $method,
            'request' => $options,
        ];
        $client = new Client();
        try {
            $response = $client->request($method, $url, $options);
            $message = 'Cimpress request successfully';
        } catch (RequestException $exc) {
            $context['exception_message'] = $exc->getMessage();
            $message = 'Cimpress request failed';
            $response = $exc->getResponse();
        } catch (\Throwable $exc) {
            $context['exception_message'] = $exc->getMessage();
            $this->getLogger()->info('Cimpress request exception', $context);
            throw $exc;
        }

        $result = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = [];
        }

        $context['response'] = !empty($result) ? $result : $response->getBody()->getContents();
        $context['status_code'] = $response->getStatusCode();
        $this->getLogger()->info($message, $context);

        return $result;
    }

    /**
     * Get Authorize token
     *
     * @param bool $bearer
     *
     * @return string
     */
    protected function getToken($bearer = false): string
    {
        return $bearer ? sprintf('Bearer %s', $this->token) : $this->token;
    }

    /**
     * Get logger
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

}
