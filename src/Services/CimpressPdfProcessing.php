<?php

namespace Cimpress\Services;

use GuzzleHttp\Client;

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
     *
     * @return mixed
     * @throws \Exception
     */
    public function mergePages(array $pdfUrls, string $callbackUrl = '')
    {
        $client = new Client();

        try {
            $response = $client->post(
                "https://pdf.prepress.documents.cimpress.io/v2/mergePages",
                [
                    'headers' => [
                        'Authorization' => $this->getToken(true),
                    ],
                    'json'    => [
                        'PdfUrls'     => $pdfUrls,
                        'CallbackUrl' => $callbackUrl,
                    ],
                ]
            );
        } catch (\Exception $e) {
            throw $e;
        }

        return json_decode($response->getBody()->getContents());
    }
}
