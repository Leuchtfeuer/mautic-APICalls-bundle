<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use http\Env\Response;
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
     * @return int
     */

    public function sendRequest(string $value, string $url, string $method): int
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