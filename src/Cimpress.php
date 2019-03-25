<?php

namespace Cimpress;

use Cimpress\Services\Auth\CimpressAuthInterface;
use Cimpress\Services\Cache\CacheInterface;
use Cimpress\Services\Cache\CacheJwtToken;
use Cimpress\Services\CimpressServiceInterface;
use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerInterface;

/**
 * Class Cimpress.
 * It provides the cimpress services using __call magic method.
 * These methods require 1 parameter (client_id) if the Cimpress object was created with auth v1,
 * and require 2 parameters (client_id, client_secret) if the Cimpress object was created with auth v2.
 *
 * @method Cimpress\Services\CimpressPrepress prepress() Get prepress service
 * @method Cimpress\Services\CimpressPdfProcessing pdfProcessing() Get PDF Processing service
 */
class Cimpress
{
    /**
     * @var array $config The cimpress configuration
     *
     * authVersion: 'v1' // 'v1' (default) or 'v2'
     *
     * credentials: (used by authentication v1)
     *      username: "%env(CIMPRESS_USERNAME)%"
     *      password: "%env(CIMPRESS_PASSWORD)%"
     *      connection: 'CimpressADFS'
     *      scope: 'openid name email'
     *      api_type: 'app'
     *
     * cacheType: 'database' (default), 'redis' or 'memory'
     *
     * jwtToken: (cache config)
     *      enableCaching: true // Default is false
     *
     *      // Options for 'database' cache type:
     *      tokenTableName: 'jwt_token'
     *      databaseUrl: 'mysql://user:pass@samplehost:3306/db?password=pass' // Alternative for databaseDsn
     *      databaseDsn: 'mysql:host=samplehost;port=3306'                    // Alternative for databaseUrl
     *      databaseUser: 'username'                                          // Alternative for databaseUrl
     *      databasePassword: 'password'                                      // Alternative for databaseUrl
     *
     *      // Options for 'redis' cache type
     *      host: '127.0.0.1'
     *      port: 6379 // Optional
     *      database: '5'
     *      password: 'somepass' // Optional
     *
     * http: Guzzle constructor options
     */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /** @var CimpressAuthInterface */
    private $authenticator;

    /**
     * Cimpress constructor.
     *
     * @param array $config
     * @param LoggerInterface $logger
     * @param CimpressAuthInterface $authenticator Optional. Default to authenticator v1 using database cache.
     */
    public function __construct(array $config, LoggerInterface $logger, CimpressAuthInterface $authenticator = null)
    {
        $this->config        = $config;
        $this->logger        = $logger;
        $this->authenticator = $authenticator;

        if (!$this->authenticator) {
            $this->authenticator = $this->buildAuthenticator(
                $this->buildCache(),
                new HttpClient($this->config['http'] ?? [])
            );
        }
    }

    /**
     * Call Cimpress services dynamically
     *
     * Like: $cimpress->prepress($relatedClientId)
     *       $cimpress->pdfProcessing($relatedClientId)
     *
     * @param string $name      The service name
     * @param array  $arguments The arguments required to authenticate,
     *    according to the auth version defined in config.
     *    V1 requires 'client_id' param and 'credentials' config key
     *    V2 requires 'client_id' and 'client_secret' params (does not require 'credentials' config key)
     *
     * @return CimpressServiceInterface
     * @throws \Exception
     */
    public function __call(string $name, array $arguments): CimpressServiceInterface
    {
        return $this->buildCimpressService($name, $arguments);
    }

    /**
     * Build a cache according to the cacheType config
     * The cache class must implement CacheInterface and have the name Cimpress\Services\Cache\Cache{$name}
     *
     * @return CacheInterface
     */
    private function buildCache(): CacheInterface
    {
        $cacheType = $this->config['cacheType'] ?? 'database';
        return $this->buildGenericObject(
            sprintf('%s\\Services\\Cache\\Cache%s', __NAMESPACE__, ucfirst($cacheType)),
            CacheInterface::class,
            [$this->config['jwtToken'] ?? []]
        );
    }

    /**
     * Build a Cimpress Authenticator according to the authVersion config
     * The auth class must implement CimpressAuthInterface and have the name Cimpress\Services\Auth\CimpressAuth{VersionId}
     *
     * @param CacheInterface $cache
     * @param HttpClient $httpClient
     * @param array $credentials
     * @return CimpressAuthInterface
     */
    private function buildAuthenticator(CacheInterface $cache, HttpClient $httpClient): CimpressAuthInterface
    {
        $version = $this->config['authVersion'] ?? 'v1';
        return $this->buildGenericObject(
            sprintf('%s\\Services\\Auth\\CimpressAuth%s', __NAMESPACE__, ucfirst($version)),
            CimpressAuthInterface::class,
            [$this->config['credentials'] ?? [], $httpClient, $cache, $this->logger]
        );
    }

    /**
     * Build a Cimpress Service by its name
     * The service class must implement CimpressServiceInterface and have the name Cimpress\Services\Cimpress{name}
     *
     * @param string $name
     * @param array $credentials
     * @return CimpressServiceInterface
     * @throws DomainException if the name does not match to a valid service class
     */
    private function buildCimpressService(string $name, array $credentials): CimpressServiceInterface
    {
        return $this->buildGenericObject(
            sprintf('%s\\Services\\Cimpress%s', __NAMESPACE__, ucfirst($name)),
            CimpressServiceInterface::class,
            [$credentials, $this->authenticator, $this->logger]
        );
    }

    /**
     * Build an object of a class and check whether it implements an interface.
     *
     * @param string $className
     * @param string $interface
     * @param array $constructorArgs
     * @return mixed
     */
    private function buildGenericObject(string $className, string $interface, array $constructorArgs = [])
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \DomainException(sprintf('Class %s does not exist', $className), 0, $e);
        }
        if (!$reflectionClass->implementsInterface($interface)) {
            throw new \DomainException(sprintf('Class %s does not implement %s', $className, $interface));
        }
        if (!$reflectionClass->isInstantiable()) {
            throw new \DomainException(sprintf('Class %s is not instantiable', $className));
        }

        if (empty($constructorArgs)) {
            return $reflectionClass->newInstance();
        }

        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
