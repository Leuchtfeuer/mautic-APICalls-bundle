<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;

class ContactProcessorService
{
    public function __construct(private ApiCallsService $apiCallsService){}


    /**
     * @param ArrayCollection|Lead $contacts
     * @param array $properties
     */
    public function processContacts(ArrayCollection|Lead $contacts, array $properties): void
    {
        foreach ($contacts as $contact) {
            $tokenizedValue = TokenHelper::findLeadTokens($properties['body'], $contact->getProfileFields(), true);
            $this->apiCallsService->sendRequest($tokenizedValue, $properties['url'], $properties['method']);
        }

    }
}