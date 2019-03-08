<?php

namespace Cimpress\Services;

/**
 * Class CimpressPdfProcessing
 */
class CimpressPdfProcessing extends BaseCimpress
{
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
            'https://pdf.prepress.documents.cimpress.io/v2/mergePages?asynchronous=true',
            [
                'PdfUrls' => $pdfUrls,
                'CallbackUrl' => $callbackUrl,
            ]
        );
    }
}
