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
        public readonly string $body = '',
        public readonly string $urlParameters = '',
        public readonly string $username = '',
        public readonly string $password = '',
        public readonly string $contactField = '',
        public readonly string $regex = ''
    ) {}

    public function getTokenizedValue(LeadEventLog $lead): string
    {
        $tokenizedValue = '';

        if ($lead->getLead()) {

            $sourceText = !empty($this->body) ? $this->body : $this->urlParameters;

            $tokenizedValue = TokenHelper::findLeadTokens(
                $sourceText,
                $lead->getLead()->getProfileFields(),
                true
            );
        }

        return !is_array($tokenizedValue) ? $tokenizedValue : '';
    }

     /**
      * @return array{url: string, options: array<string, mixed>}
      */
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