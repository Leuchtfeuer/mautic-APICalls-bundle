<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;

class ContactProcessorService
{
    public function __construct(private ApiCallsService $apiCallsService){}

    /**
     * @param ArrayCollection<int, Lead>|Lead $contacts
     * @param array<string, string> $properties
     */
    public function processContacts(ArrayCollection|Lead $contacts, array $properties): void
    {
        // @phpstan-ignore-next-line
        foreach ($contacts as $contact) {
            // @phpstan-ignore-next-line
            $tokenizedValue = TokenHelper::findLeadTokens($properties['body'], $contact->getProfileFields(), true);
            $this->apiCallsService->sendRequest($tokenizedValue, $properties['url'], $properties['method']);
        }

    }
}