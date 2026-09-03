<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Factory\ApiCallPropertiesDTOFactory;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use PHPUnit\Framework\TestCase;

final class ContactProcessorServiceTest extends TestCase
{
    public function testProcessContacts(): void
    {
        $properties = [
            'url'         => 'https://api.example.com/webhook',
            'method'      => 'POST',
            'contentType' => 'application/json',
            'body'        => '{"test": "data"}',
        ];

        $leads = [
            $this->createStub(LeadEventLog::class),
            $this->createStub(LeadEventLog::class),
        ];

        $dto = $this->createStub(ApiCallPropertiesDTO::class);

        $apiCallsService = $this->createMock(ApiCallsService::class);
        $apiCallsService->expects($this->exactly(2))
            ->method('sendRequest')
            ->with($this->isInstanceOf(LeadEventLog::class), $dto);

        $dtoFactory = $this->createMock(ApiCallPropertiesDTOFactory::class);
        $dtoFactory->expects($this->exactly(2))
            ->method('createFromProperties')
            ->with($properties)
            ->willReturn($dto);

        $service = new ContactProcessorService($apiCallsService, $dtoFactory);

        $service->processContacts($properties, $leads);
    }

    public function testProcessContactsWithEmptyLeads(): void
    {
        $properties = [
            'url'         => 'https://api.example.com/webhook',
            'method'      => 'POST',
            'contentType' => 'application/json',
        ];

        $leads = [];

        $apiCallsService = $this->createMock(ApiCallsService::class);
        $apiCallsService->expects($this->never())
            ->method('sendRequest');

        $dtoFactory = $this->createMock(ApiCallPropertiesDTOFactory::class);
        $dtoFactory->expects($this->never())
            ->method('createFromProperties');

        $service = new ContactProcessorService($apiCallsService, $dtoFactory);

        $service->processContacts($properties, $leads);
    }
}
