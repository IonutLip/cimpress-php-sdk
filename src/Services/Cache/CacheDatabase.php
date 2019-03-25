<?php

namespace Cimpress\Services\Cache;

/**
 * Class CacheDatabase
 */
class CacheDatabase implements CacheInterface
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
     * @var array
     */
    private $data;

    /**
     * CacheJwtToken constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config     = $config;
        $this->data       = [];
        $this->connection = $this->getConnection();

        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->createJwtTokenTable();
    }

    /**
     * Get cached jwt access token
     * @param string $cacheId
     * @return midex
     */
    public function fetch(string $cacheId)
    {
        return $this->exists($cacheId) ? $this->data[$cacheId] : null;
    }

    /**
     * {@inheritdoc}
     * @param string $cacheId
     * @return midex
     */
    public function exists(string $cacheId): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        try {
            $dbLastToken = $this->data[$cacheId] ?? $this->selectJwtToken($cacheId);

            if (!$dbLastToken) {
                return false;
            }

            $expTime = $this->decodeToken($dbLastToken)['exp'] ?? false;
            if (!$expTime) {
                unset($this->data[$cacheId]);
                return false;
            }

            if (time() > $expTime) {
                unset($this->data[$cacheId]);
                return false;
            }

            $this->data[$cacheId] = $dbLastToken;

            return true;
        } catch (\Exception $e) {
            // ignore
        }

        unset($this->data[$cacheId]);
        return false;
    }

    /**
     * {@inheritdoc}
     * @param string $cacheId
     * @param string $accessToken
     * @param int $ttl
     * @return void
     */
    public function store(string $cacheId, $accessToken, int $ttl = 0): void
    {
        $sql = sprintf(
            'REPLACE INTO `%s` (id, access_token) VALUES (:id, :token)',
            $this->getTokenTable()
        );
        try {
            $this->connection
                ->prepare($sql)
                ->execute([
                    ':id'    => $cacheId,
                    ':token' => $accessToken,
                ]);

        } catch (\PDOException $e) {
            try {
                $this->createJwtTokenTable();
                $this->connection
                    ->prepare($sql)
                    ->execute([
                        ':id'    => $cacheId,
                        ':token' => $accessToken,
                    ]);
            } catch (\Exception $e) {
                // ignore
            }

        } catch (\Exception $e) {
            // ignore
        }
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
     * Select access token from jwt
     * @param string $cacheId
     * @return string|bool
     */
    private function selectJwtToken(string $cacheId): string
    {
        $sql = sprintf(
            'SELECT access_token FROM `%s` WHERE id = :id',
            $this->getTokenTable()
        );

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $cacheId]);
            $record = $stmt->fetch();

            return $record['access_token'] ?? false;
        } catch (\Exception $e) {
            return false;
        }

        return false;
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
                      `id`           VARCHAR(50) NOT NULL DEFAULT '',
                      `access_token` VARCHAR(2048) NOT NULL DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    )
            ";

            $this->connection->exec($sql);
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
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isCachingEnabled(): bool
    {
        return (bool) ($this->config['enableCaching'] ?? false);
    }

    /**
     * Get PDO database connection
     *
     * @return \PDO
     */
    private function getConnection()
    {
        if (isset($this->config['databaseDsn'])) {
            $dsn  = $this->config['databaseDsn'];
            $user = $this->config['databaseUser'] ?? '';
            $pass = $this->config['databasePassword'] ?? '';
        }
        if (isset($this->config['databaseUrl'])) {
            $credentials = $this->parseDbCredential();

            $dsn = sprintf(
                "%s:host=%s;port=%s;dbname=%s",
                $credentials['scheme'],
                $credentials['host'],
                $credentials['port'],
                $credentials['db']
            );
            $user = $credentials['username'];
            $pass = $credentials['password'];
        }

        return new \PDO($dsn, $user, $pass);
    }

    /**
     * Parse database credential from database url
     *
     * @return array
     */
    private function parseDbCredential()
    {
        if (!isset($this->config['databaseUrl'])) {
            throw new \LogicException('Failed to create a database cache (missing databaseUrl config)');
        }

        $parsedUrl = parse_url($this->config['databaseUrl']);

        return [
            'scheme'   => $parsedUrl['scheme'],
            'port'     => $parsedUrl['port'],
            'host'     => $parsedUrl['host'],
            'db'       => str_replace('/', '', $parsedUrl['path']),
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
        $parsedUrl = parse_url($this->config['databaseUrl']);
        parse_str(parse_url($this->config['databaseUrl'], PHP_URL_QUERY), $output);

        return $output['password'] ?? $parsedUrl['pass'] ?? null;
    }
}
