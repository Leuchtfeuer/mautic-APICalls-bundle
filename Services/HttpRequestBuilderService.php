<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class HttpRequestBuilderService
{
    /**
     * @return array{url: string, options: array<string, mixed>}
     */
    public function buildUrlAndOptions(string $value, ApiCallPropertiesDTO $dto): array
    {
        $url = $dto->url;

        // Build url with GET parameters
        if ($dto->method === 'GET' && !empty($value)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url . $separator . $value;
        }

        // Options for sending request
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $dto->contentType,
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        // If not GET method then set body
        if ($dto->method !== 'GET') {
            $options['body'] = $value;
        }

        // If there are user and password then auth_basic
        if (!empty($this->username) && !empty($this->password)) {
            $options['auth_basic'] = [$this->username, $this->password];
        }

        return [
            'url' => $url,
            'options' => $options
        ];
    }
}