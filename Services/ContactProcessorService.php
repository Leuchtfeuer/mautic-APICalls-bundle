<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;



use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Helper\TokenHelper;


class ContactProcessorService
{
    public function __construct(private ApiCallsService $apiCallsService){}

    /**
     * @param array<string, string> $properties
     */
    public function processContacts(array $properties,  array $leads): void
    {
        /** @var LeadEventLog $lead */
        foreach ($leads as $lead) {

            if (empty($properties['url_parameters']))
            {
                $tokenizedValue = TokenHelper::findLeadTokens(
                $properties['body'],
                $lead->getLead()->getProfileFields(),
                true
                );
            } else {
                $tokenizedValue = TokenHelper::findLeadTokens(
                $properties['url_parameters'],
                $lead->getLead()->getProfileFields(),
                true
                );
            }

            if (is_string($tokenizedValue)) {
                $this->apiCallsService->sendRequest(
                    $lead,
                    $tokenizedValue,
                    $properties['url'],
                    $properties['method'],
                    $properties['contentType'],
                    $properties['username'] ?? '',
                    $properties['password'] ?? '',
                    $properties['contact_field'] ?? '',
                    $properties['regex'] ?? ''
                );
            }
        }
    }
}