<?php
namespace Cimpress\Services\Auth;

use Cimpress\Entity\AuthToken;
use Cimpress\Services\Cache\CacheInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class CimpressAuthV2 extends BaseAuth
{
    const AUTH_URL = 'https://oauth.cimpress.io/v2/token';

    /**
     * {@inheritdoc}
     *
     * @param array $config Empty array (no config available for V2)
     * @param HttpClientInterface $httpClient
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(array $config, HttpClientInterface $httpClient, CacheInterface $cache, LoggerInterface $logger)
    {
        parent::__construct($config, $httpClient, $cache, $logger);
    }

    /**
     * {@inheritdoc}
     * @param array $credentials Array with two values: client_id and client_secret
     * @return AuthToken
     */
    protected function getTokenFromAPI(array $credentials): AuthToken
    {
        // Request token
        $params = [
            'client_id'     => $credentials[0],
            'client_secret' => $credentials[1],
            'grant_type'    => 'client_credentials',
            'audience'      => 'https://api.cimpress.io/',
        ];
        $options = ['form_params' => $params];

        try {
            $response = $this->httpClient->post(self::AUTH_URL, $options);
        } catch (RequestException $e) {
            $errorMessage = 'Cimpress authentication failed';
            $errorContext = [
                'message' => $e->getMessage(),
                'url'     => self::AUTH_URL,
                'request' => $options,
            ];
            if ($e->hasResponse()) {
                $response                        = $e->getResponse();
                $errorContext['response_status'] = $response->getStatusCode();
                $errorContext['response']        = $response->getBody()->getContents();
            }
            $this->logger->error($errorMessage, $errorContext);
            throw new \Exception($errorMessage, 0, $e);
        } catch (\Exception $e) {
            $errorMessage = 'Cimpress authorization failed';
            $errorContext = [
                'message' => $e->getMessage(),
                'url'     => self::AUTH_URL,
                'request' => $options,
            ];
            $this->logger->error($errorMessage, $errorContext);
            throw new \Exception($errorMessage, 0, $e);
        }

        // Check response status
        $body = $response->getBody()->getContents();
        $code = (string) $response->getStatusCode();
        if (substr($code, 0, 1) !== '2') {
            $errorMessage = 'Failed to get auth token';
            $errorContext = [
                'request'  => $options,
                'response' => ['status' => $code, 'body' => $body],
            ];
            $this->logger->error($errorMessage, $errorContext);
            throw new \Exception($errorMessage);
        }

        // Parse/validate response data
        $data = json_decode($body);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $errorMessage = 'Failed to decode response data';
            $errorContext = [
                'raw_data'   => $body,
                'json_error' => [
                    'code'    => json_last_error(),
                    'message' => json_last_error_msg(),
                ],
            ];
            $this->logger->error($errorMessage, $errorContext);
            throw new \UnexpectedValueException($errorMessage);
        }
        if (!property_exists($data, 'access_token')) {
            $errorMessage = 'Auth API response does not have access_token property';
            $errorContext = ['response_data' => $data];
            $this->logger->error($errorMessage, $errorContext);
            throw new \UnexpectedValueException($errorMessage);
        }

        $this->logger->info('Cimpress authorization', [
            'url'      => self::AUTH_URL,
            'request'  => $options,
            'response' => $body,
        ]);

        return new AuthToken($data->token_type ?? 'Bearer', $data->access_token, $data->expires_in ?? 0);
    }
}
