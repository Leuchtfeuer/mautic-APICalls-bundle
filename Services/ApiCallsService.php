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
        ];

        $response = $this->client->request($method, $url, $options);
        $this->checkStatusCode($response);
    }


    /**
     * @param ResponseInterface $response
     */
    public function checkStatusCode($response):void
    {
        $response->getContent();
    }


}