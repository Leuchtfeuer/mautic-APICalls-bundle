<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class ApiCallsService
{
    public function __construct(private HttpClientInterface $client)
    {}

    /**
     * @param array<string, mixed>|string $value
     * @param string $url
     * @param string $method
     * @return int HTTP status code
     */

    public function sendRequest(array|string $value, string $url, string $method): int
    {
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
            ],
            'json' => $value,
            'verify_peer' => false,
            'verify_host' => true,
        ];

        $response = $this->client->request($method, $url, $options);
        $this->checkStatusCode($response);

        return $response->getStatusCode();
    }


    /**
     * @param ResponseInterface $response
     */
    public function checkStatusCode($response):void
    {
        $response->getContent();
    }


}