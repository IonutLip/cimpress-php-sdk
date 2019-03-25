<?php

namespace Cimpress\Services;

use Cimpress\Services\Auth\CimpressAuthInterface;
use Psr\Log\LoggerInterface;

interface CimpressServiceInterface
{
    /**
     * Create a service
     *
     * @param array $credentials
     * @param CimpressAuthInterface $authenticator
     * @param LoggerInterface $logger
     */
    public function __construct(array $credentials, CimpressAuthInterface $authenticator, LoggerInterface $logger);
}