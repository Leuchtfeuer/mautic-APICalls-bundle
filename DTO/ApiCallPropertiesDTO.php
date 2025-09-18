<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\DTO;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Helper\TokenHelper;

 class ApiCallPropertiesDTO
{
    public function __construct(
        public readonly string $url,
        public readonly string $method,
        public readonly string $contentType,
        public readonly ?string $body = null,
        public readonly ?string $urlParameters = null,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $contactField = null,
        public readonly ?string $regex = null
    ) {}

    public function getTokenizedValue(LeadEventLog $lead): ?string
    {
        if (empty($this->urlParameters)) {
            $tokenizedValue = TokenHelper::findLeadTokens(
                $this->body,
                $lead->getLead()->getProfileFields(),
                true
            );
        } else {
            $tokenizedValue = TokenHelper::findLeadTokens(
                $this->urlParameters,
                $lead->getLead()->getProfileFields(),
                true
            );
        }

        return is_string($tokenizedValue) ? $tokenizedValue : null;
    }

    public function buildUrlAndOptions(string $value): array
    {
        $url = $this->url;

        // Build url with GET parameters
        if ($this->method === 'GET' && !empty($value)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url = $url . $separator . $value;
        }

        // Options for sending request
        $options = [
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => $this->contentType,
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ];

        // If not GET method then set body
        if ($this->method !== 'GET') {
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