<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class ApiCallsService
{
    public function __construct(private HttpClientInterface $client)
    {}

    /**
     * @param string $value
     * @param string $url
     * @param string $method
     * @param string $contentType
     */

    public function sendRequest(string $value, string $url, string $method, string $contentType): void
    {
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $contentType,
            ],
            'body' => $value,
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        $currentUrl = $url;
        $maxRedirects = 5;
        $redirectCount = 0;

        while ($redirectCount < $maxRedirects) {

            $response = $this->client->request($method, $currentUrl, $options);

            $statusCode = $response->getStatusCode();

            if (!in_array($statusCode, [301, 302, 303, 307, 308])) {
                break;
            }

            $locationHeader = $response->getHeaders(false)['location'][0] ?? null;
            if (!$locationHeader) {
                break;
            }

            $currentUrl = $locationHeader;
            $redirectCount++;
        }

        if ($response !== null) {
            $this->checkIfResponseValid($response);
        }
    }

    /**
     * @param ResponseInterface $response
     */
    public function checkIfResponseValid($response):void
    {
        $response->getContent();
    }


}