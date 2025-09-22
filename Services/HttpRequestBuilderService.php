<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class HttpRequestBuilderService
{


    public function __construct(private UrlBuilderService $urlBuilderService)
    {}
    /**
     * @return array{url: string, options: array<string, mixed>}
     */
    public function buildUrlAndOptions(string $value, ApiCallPropertiesDTO $dto): array
    {
        // Build url with GET parameters
        if ($dto->method === 'GET' && !empty($value)) {

            $url = $this->urlBuilderService->appendQueryString($dto->url, $value);
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
        if (!empty($dto->username) && !empty($dto->password)) {
            $options['auth_basic'] = [$dto->username, $dto->password];
        }

        return [
            'url' => $url,
            'options' => $options
        ];
    }
}