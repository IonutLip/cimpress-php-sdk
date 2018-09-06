<?php

namespace Cimpress\Cache\JwtToken;

/**
 * Class CacheJwtToken
 */
class CacheJwtToken
{
    /**
     * @var array $config
     */
    private $config;

    /**
     * @var \PDO $connection
     */
    private $connection;

    /**
     * jwt db id for table
     */
    private $dbId;

    /**
     * CacheJwtToken constructor.
     *
     * @param string $dbId
     * @param array  $config
     */
    public function __construct(string $dbId, array $config)
    {
        $this->dbId       = $dbId;
        $this->config     = $config;
        $this->connection = $this->getConnection();

        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get cached jwt access token
     *
     * @return bool
     */
    public function getCachedJwtAccessToken()
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        try {
            $dbLastToken = $this->fetchDbJwtAccessToken();

            if (!$dbLastToken) {
                return false;
            }

            $expTime = $this->decodeToken($dbLastToken)['exp'] ?? false;
            if (!$expTime) {
                return false;
            }

            $expireInSeconds = $this->config['jwtToken']['tokenExpireIn'] ?? 60;
            $crtTime         = time() - $expireInSeconds;

            if ($crtTime <= $expTime) {
                return $dbLastToken;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Update|create jwt access token in db
     *
     * @param string $accessToken
     *
     * @return bool
     */
    public function updateDbJwtAccessToken(string $accessToken)
    {
        if ($this->selectJwtToken()) {
            $this->updateJwtToken($accessToken);

            return true;
        }

        $this->createJwtToken($accessToken);

        return true;
    }

    /**
     * Decode|deserialize jwt access token
     *
     * @param string $token
     *
     * @return mixed
     * @throws \Exception
     */
    private function decodeToken(string $token)
    {
        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 3) {
            throw new \Exception("Invalid JWT");
        }

        return json_decode(base64_decode($tokenParts[1]), true);
    }

    /**
     * Update existing jwt access token
     *
     * @param string $accessToken
     *
     * @return bool
     */
    private function updateJwtToken(string $accessToken)
    {
        try {
            $this->connection
                ->query("UPDATE " . $this->getTokenTable() . " SET access_token = '" . $accessToken . "' WHERE id = '" . $this->dbId . "'")
                ->execute();
        } catch (\PDOException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create|insert new entry into caching table
     *
     * @param string $accessToken
     *
     * @return bool
     */
    private function createJwtToken(string $accessToken)
    {
        try {
            $this->connection
                ->query("INSERT INTO " . $this->getTokenTable() . " (`id`, `access_token`) VALUES ('" . $this->dbId . "', '" . $accessToken . "')")
                ->execute();
        } catch (\PDOException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Select access token from jwt
     *
     * @return bool
     */
    private function selectJwtToken()
    {
        try {
            $stmt   = $this->connection->query("SELECT access_token FROM " . $this->getTokenTable() . " WHERE id = '" . $this->dbId . "'");
            $record = $stmt->fetch();

            return $record['access_token'] ?? false;
        } catch (\PDOException $e) {
            $this->createJwtTokenTable();
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Fetch jwt access token from db
     *
     * @return bool
     */
    private function fetchDbJwtAccessToken()
    {
        try {
            return $this->selectJwtToken();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create table for storing cached token
     *
     * @return bool
     */
    private function createJwtTokenTable()
    {
        try {
            $sql = "CREATE TABLE `" . $this->getTokenTable() . "` (
                      `id`           VARCHAR(50) COLLATE utf8_unicode_ci   NOT NULL DEFAULT '' COMMENT 'service name',
                      `access_token` VARCHAR(2048) COLLATE utf8_unicode_ci NOT NULL,                      
                      PRIMARY KEY (`id`)
                    )
            ";
            $this->connection->exec($sql);
        } catch (\PDOException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get Token table name
     *
     * @return mixed|string
     */
    private function getTokenTable()
    {
        return $this->config['tokenTableName'] ?? 'jwt_token';
    }

    /**
     * Check is jwt token caching enabled or not
     *
     * @return bool|mixed
     */
    private function isCachingEnabled()
    {
        return $this->config['enableCaching'] ?? false;
    }

    /**
     * Get PDO database connection
     *
     * @return \PDO
     */
    private function getConnection()
    {
        $credentials = $this->parseDbCredential();

        return new \PDO(
            sprintf(
                "%s:host=%s:%s;dbname=%s",
                $credentials['scheme'],
                $credentials['host'],
                $credentials['port'],
                $credentials['db']
            ), $credentials['username'], $credentials['username']
        );
    }

    /**
     * Parse database credential from database url
     *
     * @return array
     */
    private function parseDbCredential()
    {
        $parsedUrl = parse_url($this->config['databaseUrl']);

        return [
            'scheme'   => $parsedUrl['scheme'],
            'port'     => $parsedUrl['port'],
            'host'     => $parsedUrl['host'],
            'db'       => str_replace('/', '', ($parsedUrl['path'])),
            'username' => $parsedUrl['user'],
            'password' => $this->getDbPassword(),
        ];
    }

    /**
     * Parse db password from url
     *
     * @return null
     */
    private function getDbPassword()
    {
        parse_str(parse_url($this->config['databaseUrl'], PHP_URL_QUERY), $output);

        return $output['password'] ?? null;
    }
}
