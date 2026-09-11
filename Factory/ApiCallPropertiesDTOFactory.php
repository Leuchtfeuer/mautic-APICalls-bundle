<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Factory;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;

class ApiCallPropertiesDTOFactory
{
    public function __construct(
        private readonly CampaignActionSecretService $secretService,
    ) {
    }

    /**
     * @param array<string, string> $properties
     */
    public function createFromProperties(array $properties): ApiCallPropertiesDTO
    {
        return new ApiCallPropertiesDTO(
            url: $properties['url'],
            method: $properties['method'],
            contentType: $properties['contentType'],
            body: $properties['body'] ?? '',
            urlParameters: $properties['url_parameters'] ?? '',
            username: $properties['username'] ?? '',
            password: $this->secretService->decryptIfNeeded($properties['password'] ?? null),
            contactField: $properties['contact_field'] ?? '',
            regex: $properties['regex'] ?? '',
            objectKey: $properties['object_key'] ?? '',
            valueKey: $properties['value_key'] ?? '',
            authorizationHeader: $this->secretService->decryptIfNeeded($properties['authorization_header'] ?? null),
        );
    }
}
