<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

class UrlBuilderService
{

    public function appendQueryString(string $url, string $value): string
    {
        if (empty($value)) {
            return $url;
        }

        $newParams = [];

        parse_str($value, $newParams);
        $queryString = http_build_query($newParams, '', '&');
        $separator   = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.$queryString;
    }
}
