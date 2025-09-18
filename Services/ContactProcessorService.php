<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;



use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Helper\TokenHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;


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

            $dto = new ApiCallPropertiesDTO(
                url: $properties['url'],
                method: $properties['method'],
                contentType: $properties['contentType'],
                body: $properties['body'] ?? null,
                urlParameters: $properties['url_parameters'] ?? null,
                username: $properties['username'] ?? null,
                password: $properties['password'] ?? null,
                contactField: $properties['contact_field'] ?? null,
                regex: $properties['regex'] ?? null
            );

            $this->apiCallsService->sendRequest($lead, $dto);
        }
    }
}