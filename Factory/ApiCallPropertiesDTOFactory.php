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
            body: $properties['body'] ?? '',
            urlParameters: $properties['url_parameters'] ?? '',
            username: $properties['username'] ?? '',
            password: $properties['password'] ?? '',
            contactField: $properties['contact_field'] ?? '',
            regex: $properties['regex'] ?? '',
            objectKey: $properties['object_key'] ?? '',
            valueKey: $properties['value_key'] ?? ''
        );
    }
}