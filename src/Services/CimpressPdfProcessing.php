<?php

namespace Cimpress\Services;

/**
 * Class CimpressPdfProcessing
 */
class CimpressPdfProcessing extends BaseCimpress
{
    const MERGE_PAGES_URL = 'https://pdf.prepress.documents.cimpress.io/v2/mergePages?asynchronous=true';

    /**
     * Merge multiple PDFs into a single PDF
     *
     * @param array  $pdfUrls     The list of pdf file urls to be merge
     * @param string $callbackUrl The API will return the information we want
     *
     * @return array
     * @throws \Exception
     */
    public function mergePages(array $pdfUrls, string $callbackUrl = ''): array
    {
        return $this->requestJson(
            'POST',
            self::MERGE_PAGES_URL,
            [
                'json' => [
                    'PdfUrls'     => $pdfUrls,
                    'CallbackUrl' => $callbackUrl,
                ],
            ]
        );
    }
}
