<?php

namespace Cimpress\Services;

/**
 * Class CimpressPrepress
 */
class CimpressPrepress extends BaseCimpress
{
    const FILE_PREP_URL  = 'https://prepress.documents.cimpress.io/v2/file-prep?asynchronous=true';
    const PRINT_PREP_URL = 'https://prepress.documents.cimpress.io/v2/print-prep?inline=true&noRedirect=true&asynchronous=true';

    /**
     * Prepares customer files for placement on a document, inspecting the files for potential issues.
     *
     * @param string $fileUrl      The pdf file url to be checked
     * @param string $parameterUrl The configuration reference where API has to check to understand what it has to do
     * @param string $callbackUrl  The API will return the information we want
     * @return array
     * @throws \Exception
     */
    public function filePrep(string $fileUrl, string $parameterUrl, string $callbackUrl): array
    {
        return $this->requestJson(
            'POST',
            self::FILE_PREP_URL,
            [
                'json' => [
                    'FileUrl'       => $fileUrl,
                    'ParametersUrl' => $parameterUrl,
                    'CallbackUrl'   => $callbackUrl,
                ],
            ]
        );
    }

    /**
     * Prepares print.
     *
     * @param string $documentInstructionsUrl
     * @param string $parametersUrl
     * @return array Array with:
     *     string 'ResultUrl'
     * @throws \Exception
     * @todo DOCUMENT THIS METHOD PROPERLY
     */
    public function printPrep(string $documentInstructionsUrl, string $parametersUrl): array
    {
        return $this->requestJson(
            'POST',
            self::PRINT_PREP_URL,
            [
                'json' => [
                    'DocumentInstructionsUrl' => $documentInstructionsUrl,
                    'ParametersUrl'           => $parametersUrl,
                ],
            ]
        );
    }
}
