<?php

namespace Cimpress\Services\Auth;

use Cimpress\Entity\AuthToken;
use Cimpress\Services\Cache\CacheInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for authentication classes
 */
abstract class BaseAuth implements CimpressAuthInterface
{
    const STATUS_UNAUTHORIZED = 401;

    /**
     * Array of auth token indexed by cache id
     *
     * @var array
     */
    protected $authToken = [];

    /** @var array */
    protected $config;

    /** @var HttpClientInterface */
    protected $httpClient;

    /** @var CacheInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * {@inheritdoc}
     *
     * @param array $config
     * @param HttpClientInterface $httpClient
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, HttpClientInterface $httpClient, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->config     = $config;
        $this->httpClient = $httpClient;
        $this->cache      = $cache;
        $this->logger     = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param strgin $method HTTP method
     * @param string $url
     * @param array $options Same as the options of Guzzle::request method
     */
    public function requestAuthenticated(array $credentials, string $method, string $url, array $options = []): ResponseInterface
    {
        $token = $this->getToken($credentials);

        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }
        $options['headers'] = $this->normalizeHeaderKeys($options['headers']);

        $options['headers']['authorization'] = sprintf('%s %s', $token->tokenType, $token->accessToken);

        // Try once. If got Unauthorized (401), try again with a new token
        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                throw $e;
            }
            if (self::STATUS_UNAUTHORIZED !== $e->getResponse()->getStatusCode()) {
                throw $e;
            }

            $newToken                            = $this->getToken($credentials, true);
            $options['headers']['authorization'] = sprintf('%s %s', $newToken->tokenType, $newToken->accessToken);
            $response                            = $this->httpClient->request($method, $url, $options);
        }

        return $response;
    }

    /**
     * Normalize header keys to lowercase
     *
     * @param array $headers
     * @return array
     */
    protected function normalizeHeaderKeys(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $result[strtolower($key)] = $value;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $credentials
     * @param bool $forceGenerate Flag to ignore cache and force to generate a new token
     * @return AuthToken
     */
    protected function getToken(array $credentials, bool $forceGenerate = false): AuthToken
    {
        $cacheId = $this->getCacheId($credentials);

        if (!$forceGenerate && array_key_exists($cacheId, $this->authToken)) {
            return $this->authToken[$cacheId];
        }
        if (!$forceGenerate && $this->cache && $this->cache->exists($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $token = $this->getTokenFromAPI($credentials);

        $this->authToken[$cacheId] = $token;
        if ($this->cache) {
            $this->cache->store($cacheId, $token, $token->ttl);
        }

        return $token;
    }

    /**
     * Request a new token from Cimpress API
     *
     * @param array $credentials
     * @return AuthToken
     */
    abstract protected function getTokenFromAPI(array $credentials): AuthToken;

    /**
     * Return a unique key for the credentials
     *
     * @param array $credentials
     * @return string
     */
    protected function getCacheId($credentials): string
    {
        return md5(json_encode([
            'config'      => $this->config,
            'credentials' => $credentials,
        ]));
    }
}
