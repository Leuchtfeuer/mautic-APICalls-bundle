<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

class UrlBuilderService
{

    public function appendQueryString(string $url, string $queryString): string
    {
        if (empty($queryString)) {
            return $url;
        }

        $newParams = [];

        parse_str($queryString, $newParams);
        $queryString = http_build_query($newParams, '', '&');
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $queryString;
    }

}