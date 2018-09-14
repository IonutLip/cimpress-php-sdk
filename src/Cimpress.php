<?php

namespace Cimpress;

use Cimpress\Cache\JwtToken\CacheJwtToken;
use GuzzleHttp\Client;

/**
 * Class Cimpress
 */
class Cimpress
{
    const CLIENT_PREPRESS       = 'prepress';
    const CLIENT_PDF_PROCESSING = 'pdfProcessing';

    const BASE_URL = 'https://cimpress.auth0.com/oauth/ro';

    const TOKEN_DB_ID = [
        self::CLIENT_PREPRESS       => 'cimpress_prepress',
        self::CLIENT_PDF_PROCESSING => 'cimpress_pdf_processing',
    ];

    /**
     * @var array $config The cimpress configuration
     *
     * credentials:
     *      username: "%env(CIMPRESS_USERNAME)%"
     *       password: "%env(CIMPRESS_PASSWORD)%"
     *      connection: 'CimpressADFS'
     *       scope: 'openid name email'
     *      api_type: 'app'
     * api:
     *      prepress:
     *          filePrep:
     *              ParameterUrl: 'https://s3.amazonaws.com/om2-files-dev/mcp/preflight/fileprep/xxxxxxxxxx.json'
     * api_clients:
     *      pdf_processing:
     *          client_id: '*************'
     *      prepress:
     *          client_id: ''*************'
     * jwtToken:
     *      enableCaching: true
     *      tokenTableName: 'jwt_token'
     *      tokenExpireIn: 60         # in seconds
     *
     */
    private $config;

    /**
     * @var string $token The authorize token
     */
    private $token;

    /**
     * @var string $clientName The client name prepress|pdf_processing
     */
    private $clientName;

    /**
     * Cimpress constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Authorize for Cimpress Api
     *
     * @param string $clientId The client id
     *
     * @return $this
     * @throws \Exception
     */
    protected function authorize(string $clientId)
    {
        if (!isset($this->config['credentials'])) {
            throw new \Exception('CIMPRESS_CREDENTIAL_MISSING');
        }

        if ($this->config['jwtToken']['enableCaching'] ?? false) {
            if ($token = (new CacheJwtToken(self::TOKEN_DB_ID[$this->clientName], $this->config['jwtToken']))->getCachedJwtAccessToken()) {
                $this->token = $token;
                return $this;
            }
        }

        $client      = new Client();
        $response    = $client->post(
            self::BASE_URL,
            [
                'form_params' => array_merge(
                    [
                        'client_id' => $clientId,
                    ],
                    $this->config['credentials']
                ),
            ]
        );
        $this->token = json_decode($response->getBody())->id_token;

        if ($this->config['jwtToken']['enableCaching'] ?? false) {
            (new CacheJwtToken(self::TOKEN_DB_ID[$this->clientName], $this->config['jwtToken']))->updateDbJwtAccessToken($this->token);
        }

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

        $this->clientName = $name;

        /**
         * Authorize Cimpress api
         * $arguments[0] (clientId)
         */
        $this->authorize($arguments[0]);

        $class = __NAMESPACE__ . '\\Services\\' . sprintf('Cimpress%s', ucfirst($name));

        if (!class_exists($class)) {
            throw new \Exception('CLASS_NOT_FOUND');
        }

        return new $class($this->token);
    }
}
