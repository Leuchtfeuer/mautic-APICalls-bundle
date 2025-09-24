<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class UrlBuilderService
{

    public function appendQueryString(ApiCallPropertiesDTO $dto, string $url, string $value): string
    {
        if ($dto->method !== 'GET' || empty($value)) {
            return $url;
        }

        $newParams = [];

        parse_str($value, $newParams);
        $queryString = http_build_query($newParams, '', '&');
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $queryString;
    }

}