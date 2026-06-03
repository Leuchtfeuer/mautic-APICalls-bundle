<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class HttpRequestBuilderService
{


    public function __construct(private UrlBuilderService $urlBuilderService,  private TokenReplacementService $tokenReplacementService)
    {}
    /**
     * @return array{url: string, options: array<string, mixed>}
     */
    public function buildUrlAndOptions(string $value, ApiCallPropertiesDTO $dto, LeadEventLog $lead): array
    {
        $url = $this->tokenReplacementService->getTokenizedUrl($lead, $dto->url);
        // Build url with GET parameters
        if ($dto->method === 'GET' && !empty($value)) {
            $url = $this->urlBuilderService->appendQueryString($dto, $url, $value);
        }

        // Options for sending request
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $dto->contentType,
            ],
            'verify_peer' => true,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        if (!empty($dto->authorizationHeader)) {
            if (str_contains($dto->authorizationHeader, ':')) {
                [$headerName, $headerValue] = explode(':', $dto->authorizationHeader, 2);
                $options['headers'][trim($headerName)] = trim($headerValue);
            }
        }

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