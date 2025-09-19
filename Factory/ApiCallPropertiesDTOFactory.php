<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Factory;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class ApiCallPropertiesDTOFactory
{
    /**
     * @param array<string, string> $properties
     */
    public function createFromProperties(array $properties): ApiCallPropertiesDTO
    {
        return new ApiCallPropertiesDTO(
            url: $properties['url'],
            method: $properties['method'],
            contentType: $properties['contentType'],
            body: $properties['body'] ?? null,
            urlParameters: $properties['url_parameters'] ?? null,
            username: $properties['username'] ?? null,
            password: $properties['password'] ?? null,
            contactField: $properties['contact_field'] ?? null,
            regex: $properties['regex'] ?? null
        );
    }
}