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

}