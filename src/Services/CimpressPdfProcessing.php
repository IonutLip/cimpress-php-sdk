<?php

namespace Cimpress\Services;

/**
 * Class CimpressPdfProcessing
 */
class CimpressPdfProcessing extends BaseCimpress
{

    /**
     * CimpressPdfProcessing constructor.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    /**
     * Merge multiple PDFs into a single PDF
     *
     * @param array  $pdfUrls     The list of pdf file urls to be merge
     * @param string $callbackUrl The API will return the information we want
     */
    public function mergePages(array $pdfUrls, string $callbackUrl)
    {
        // add code for /mergePages api
    }
}
