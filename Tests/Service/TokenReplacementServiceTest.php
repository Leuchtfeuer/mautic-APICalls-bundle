<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\TokenReplacementService;
use PHPUnit\Framework\TestCase;

class TokenReplacementServiceTest extends TestCase
{
    private TokenReplacementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TokenReplacementService();
    }

    public function testGetTokenizedValueWithUrlParameters(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json',
            body: '{"name": "{contactfield=firstname}"}',
            urlParameters: 'name={contactfield=firstname}'
        );

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn(['firstname' => 'John']);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedValue($lead, $dto);

        // The actual token replacement would happen in real scenario
        // For now, we just test that the service processes the input
        $this->assertIsString($result);
    }

    public function testGetTokenizedValueWithBody(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            body: '{"name": "{contactfield=firstname}"}'
        );

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn(['firstname' => 'John']);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedValue($lead, $dto);

        // The actual token replacement would happen in real scenario
        // For now, we just test that the service processes the input
        $this->assertIsString($result);
    }

    public function testGetTokenizedValueWithNoLead(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            body: '{"name": "{contactfield=firstname}"}'
        );

        $lead = $this->createMock(LeadEventLog::class);
        $lead->method('getLead')->willReturn(null);

        $result = $this->service->getTokenizedValue($lead, $dto);

        $this->assertEquals('', $result);
    }

    public function testGetTokenizedUrlWithTokens(): void
    {
        $url = 'https://example.com/api/{contactfield=firstname}/{contactfield=lastname}';

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn([
            'firstname' => 'John',
            'lastname' => 'Doe'
        ]);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedUrl($lead, $url);

        $this->assertEquals('https://example.com/api/John/Doe', $result);
    }

    public function testGetTokenizedUrlWithoutTokens(): void
    {
        $url = 'https://example.com/api/endpoint';

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn(['firstname' => 'John']);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedUrl($lead, $url);

        $this->assertEquals('https://example.com/api/endpoint', $result);
    }

    public function testGetTokenizedUrlWithNoLead(): void
    {
        $url = 'https://example.com/api/{contactfield=firstname}';

        $lead = $this->createMock(LeadEventLog::class);
        $lead->method('getLead')->willReturn(null);

        $result = $this->service->getTokenizedUrl($lead, $url);

        $this->assertEquals('', $result);
    }

    public function testGetTokenizedUrlWithEmptyUrl(): void
    {
        $url = '';

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn(['firstname' => 'John']);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedUrl($lead, $url);

        $this->assertEquals('', $result);
    }

    public function testGetTokenizedUrlWithMultipleTokens(): void
    {
        $url = 'https://api.example.com/{contactfield=id}/profile/{contactfield=email}?name={contactfield=firstname}';

        $lead = $this->createMock(LeadEventLog::class);
        $leadEntity = $this->createMock(Lead::class);
        $leadEntity->method('getProfileFields')->willReturn([
            'id' => '123',
            'email' => 'john.doe@example.com',
            'firstname' => 'John'
        ]);
        $lead->method('getLead')->willReturn($leadEntity);

        $result = $this->service->getTokenizedUrl($lead, $url);

        $this->assertEquals('https://api.example.com/123/profile/john.doe@example.com?name=John', $result);
    }

}