<?php

namespace Cimpress\Services\Auth;

use Cimpress\Entity\AuthToken;
use Cimpress\Services\Cache\CacheInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

interface CimpressAuthInterface
{
    /**
     * Build a Cimpress Auth Service
     *
     * @param array $config Config for authentication
     * @param HttpClientInterface $httpClient
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, HttpClientInterface $httpClient, CacheInterface $cache, LoggerInterface $logger);

    /**
     * Do a request to a cimpress API end-point using a valid token for authentication.
     * If the token was expired (response status = 401), it generates a new token and make a retry.
     *
     * @param array $credentials
     * @param strgin $method HTTP method
     * @param string $url
     * @param array $options Same as the options of Guzzle::request method
     */
    public function requestAuthenticated(array $credentials, string $method, string $url, array $options = []): ResponseInterface;
}
