<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;

class ContactProcessorService
{
    public function __construct(private ApiCallsService $apiCallsService){}

    /**
     * @param array<int, Lead>|ArrayCollection<int, Lead> $contacts
     * @param array<string, string> $properties
     */
    public function processContacts(array|ArrayCollection $contacts, array $properties): void
    {
        foreach ($contacts as $contact) {
            $tokenizedValue = TokenHelper::findLeadTokens(
                $properties['body'],
                $contact->getProfileFields(),
                true
            );

            if (is_string($tokenizedValue)) {
                $this->apiCallsService->sendRequest(
                    $tokenizedValue,
                    $properties['url'],
                    $properties['method']
                );
            }
        }
    }
}