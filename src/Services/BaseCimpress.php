<?php

namespace Cimpress\Services;

/**
 * Class BaseCimpress
 */
class BaseCimpress
{
    /** @var string $token Authorize Token */
    private $token;

    /**
     * BaseCimpress constructor.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get Authorize token
     *
     * @param bool $bearer
     *
     * @return string
     */
    protected function getToken($bearer = false)
    {
        return $bearer ? sprintf('Bearer %s', $this->token) : $this->token;
    }

}