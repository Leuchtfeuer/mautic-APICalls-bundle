<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;



use Mautic\CampaignBundle\Entity\LeadEventLog;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Factory\ApiCallPropertiesDTOFactory;


class ContactProcessorService
{
    public function __construct(private ApiCallsService $apiCallsService, private ApiCallPropertiesDTOFactory $dtoFactory){}

    /**
     * @param array<string, string> $properties
     * @param array<LeadEventLog> $leads
     */
    public function processContacts(array $properties,  array $leads): void
    {
        foreach ($leads as $lead) {
            $dto = $this->dtoFactory->createFromProperties($properties);
            $this->apiCallsService->sendRequest($lead, $dto);
        }
    }
}