<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiCallsService
{


    public function __construct(private HttpClientInterface $client)
    {}

    /**
     * @param string $value
     * @param string $url
     * @param string $method
     */

    public function sendRequest(string $value, string $url, string $method = 'POST'): int
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Die URL '$url' ist ungültig.");
        }

        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'text/plain',
            ],
            'body' => $value,
        ];

        $response = $this->client->request($method, $normalizedUrl, $options);

        return $response->getStatusCode();
    }

    public function normalizeUrl(string $url): string
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

}