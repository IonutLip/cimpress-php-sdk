<?php

namespace Cimpress\Services;

/**
 * Class CimpressPrepress
 */
class CimpressPrepress extends BaseCimpress
{

    /**
     * CimpressPrepress constructor.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    /**
     * Prepares customer files for placement on a document, inspecting the files for potential issues.
     *
     * @param string $fileUrl      The pdf file url to be checked
     * @param string $parameterUrl The configuration reference where API has to check to understand what it has to do
     * @param string $callbackUrl  The API will return the information we want
     */
    public function filePrep(string $fileUrl, string $parameterUrl, string $callbackUrl)
    {
        // add code for /file-prep api
    }
}
