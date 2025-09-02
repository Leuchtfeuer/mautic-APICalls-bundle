<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

class ApiCallsService
{
    public function buildBodyValueArrayForApiRequest(string $tokenValue, string $tokens, string $method, string $url): array
    {
        $values = explode(' ', trim($tokenValue));
        preg_match_all('/\{contactfield=(.*?)\}/', $tokens, $matches);

        $fields = $matches[1] ?? [];

        $result = [
            'url' => $url,
            'methode' => $method,
        ];

        foreach ($fields as $index => $fieldName) {
            $result[$fieldName] = $values[$index] ?? null;
        }

        return $result;
    }

}