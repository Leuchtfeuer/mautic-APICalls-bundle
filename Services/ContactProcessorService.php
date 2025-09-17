<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;

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

            $tokenizedValue = TokenHelper::findLeadTokens(
                $properties['body'],
                $lead->getLead()->getProfileFields(),
                true
            );

            if (is_string($tokenizedValue)) {
                $this->apiCallsService->sendRequest(
                    $lead,
                    $tokenizedValue,
                    $properties['url'],
                    $properties['method'],
                    $properties['contentType'],
                    $properties['username'] ?? '',
                    $properties['password'] ?? '',
                    $properties['url_parameters'] ?? ''
                );
            }
        }
    }
}