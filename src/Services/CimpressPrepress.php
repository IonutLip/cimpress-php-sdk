<?php

namespace Cimpress\Services;

/**
 * Class CimpressPrepress
 */
class CimpressPrepress extends BaseCimpress
{
    /**
     * Prepares customer files for placement on a document, inspecting the files for potential issues.
     *
     * @param string $fileUrl      The pdf file url to be checked
     * @param string $parameterUrl The configuration reference where API has to check to understand what it has to do
     * @param string $callbackUrl  The API will return the information we want
     *
     * @return array
     * @throws \Exception
     */
    public function filePrep(string $fileUrl, string $parameterUrl, string $callbackUrl): array
    {
        return $this->requestJson(
            'https://prepress.documents.cimpress.io/v2/file-prep?asynchronous=true',
            [
                'FileUrl'       => $fileUrl,
                'ParametersUrl' => $parameterUrl,
                'CallbackUrl'   => $callbackUrl,
            ]
        );
    }
}
