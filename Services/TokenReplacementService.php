<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Helper\TokenHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;

class TokenReplacementService
{

    public function getTokenizedValue(LeadEventLog $lead, ApiCallPropertiesDTO $dto): string
    {
        $tokenizedValue = '';

        if ($lead->getLead()) {
            $tokenizedValue = TokenHelper::findLeadTokens(
                $dto->body,
                $lead->getLead()->getProfileFields(),
                true
            );
        }

        return !is_array($tokenizedValue) ? $tokenizedValue : '';
    }


    public function getTokenizedUrl(LeadEventLog $lead, string $url): string
    {
        $tokenizedValue = '';

        if ($lead->getLead()) {
            $tokenizedValue = TokenHelper::findLeadTokens(
                $url,
                $lead->getLead()->getProfileFields(),
                true
            );
        }

        return !is_array($tokenizedValue) ? $tokenizedValue : '';
    }

}