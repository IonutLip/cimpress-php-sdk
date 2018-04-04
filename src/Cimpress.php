<?php

namespace Cimpress;

use GuzzleHttp\Client;

/**
 * Class Cimpress
 */
class Cimpress
{
    const BASE_URL = 'https://cimpress.auth0.com/oauth/ro';

    /**
     * @var array $credentials The cimpress credentials
     */
    private $credentials;

    /**
     * @var string $token The authorize token
     */
    private $token;

    /**
     * Cimpress constructor.
     *
     * @param array  $credentials The cimpress credentials
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Authorize for Cimpress Api
     *
     * @param string $clientId The client id
     *
     * @return $this
     */
    protected function authorize(string $clientId)
    {
        $client      = new Client();
        $response    = $client->post(
            self::BASE_URL,
            [
                'form_params' => array_merge(
                    [
                        'client_id' => $clientId,
                    ],
                    $this->credentials
                ),
            ]
        );
        $this->token = json_decode($response->getBody())->id_token;

        return $this;
    }

    /**
     * Call Cimpress services dynamically
     *
     * Like: $cimpress->prepress($relatedClientId)
     *       $cimpress->pdfProcessing($relatedClientId)
     *
     * @param string $name      The service name
     * @param array  $arguments The arguments
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $name, array $arguments)
    {

        if (!isset($arguments[0]) && 'string' === gettype($arguments[0])) {
            throw new \Exception('INVALID_ARGUMENT');
        }

        /**
         * Authorize Cimpress api
         * $arguments[0] (clientId)
         */
        $this->authorize($arguments[0]);

        $class = __NAMESPACE__ . '\\' . sprintf('Cimpress%s', ucfirst($name));

        if (!class_exists($class)) {
            throw new \Exception('CLASS_NOT_FOUND');
        }

        return new $class($this->token);
    }
}
