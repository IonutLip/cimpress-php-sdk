<?php

namespace Cimpress\Entity;

class AuthToken
{
    /**
     * @var string 'Bearer'
     */
    public $tokenType;

    /**
     * @var string Token hash
     */
    public $accessToken;

    /**
     * @var int Time to live (in seconds)
     */
    public $ttl;

    public function __construct(string $tokenType, string $accessToken, int $ttl = 0)
    {
        $this->tokenType   = $tokenType;
        $this->accessToken = $accessToken;
        $this->ttl         = $ttl;
    }
}
