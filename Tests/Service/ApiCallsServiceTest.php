<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\TokenReplacementService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\PropertySearchService;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ApiCallsServiceTest extends MauticMysqlTestCase
{
    private function createMockLeadEventLog(): LeadEventLog
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getProfileFields')->willReturn(['firstname' => 'John', 'email' => 'john@example.com']);

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        return $leadEventLog;
    }

    public function testSendRequestCallsCorrectServices(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('success', ['http_code' => 200])
        ]);

        $leadModel = $this->createMock(LeadModel::class);
        $httpRequestBuilderService = $this->createMock(HttpRequestBuilderService::class);
        $tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $urlBuilderService = $this->createMock(UrlBuilderService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}',
            username: 'user',
            password: 'pass'
        );

        $tokenReplacementService->expects($this->once())
            ->method('getTokenizedValue')
            ->willReturn('{"test": "data"}');

        $httpRequestBuilderService->expects($this->once())
            ->method('buildUrlAndOptions')
            ->with('{"test": "data"}', $dto)
            ->willReturn([
                'url' => 'https://api.example.com/webhook',
                'options' => [
                    'headers' => [
                        'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                        'Content-Type' => 'application/json',
                    ],
                    'body' => '{"test": "data"}',
                    'auth_basic' => ['user', 'pass']
                ]
            ]);

        $service = new ApiCallsService($httpClient, $leadModel, $httpRequestBuilderService, $tokenReplacementService, $urlBuilderService, $propertySearchService);
        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        // Just verify the method completed without exception
        $this->assertTrue(true);
    }

    public function testSendRequestWithRedirects(): void
    {
        $requestsCount = 0;
        $capturedRequests = [];

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestsCount, &$capturedRequests) {
            $capturedRequests[] = [
                'method' => $method,
                'url' => $url,
                'options' => $options
            ];
            $requestsCount++;

            if ($requestsCount === 1) {
                return new MockResponse('', [
                    'http_code' => 302,
                    'response_headers' => ['Location' => 'https://api.example.com/redirected']
                ]);
            }
            return new MockResponse('success', ['http_code' => 200]);
        });

        $leadModel = $this->createMock(LeadModel::class);
        $httpRequestBuilderService = $this->createMock(HttpRequestBuilderService::class);
        $tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $urlBuilderService = $this->createMock(UrlBuilderService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}'
        );

        $tokenReplacementService->method('getTokenizedValue')->willReturn('{"test": "data"}');
        $httpRequestBuilderService->method('buildUrlAndOptions')->willReturn([
            'url' => 'https://api.example.com/webhook',
            'options' => [
                'headers' => [
                    'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                    'Content-Type' => 'application/json',
                ],
                'body' => '{"test": "data"}'
            ]
        ]);

        $urlBuilderService->method('appendQueryString')->willReturn('https://api.example.com/redirected');

        $service = new ApiCallsService($httpClient, $leadModel, $httpRequestBuilderService, $tokenReplacementService, $urlBuilderService, $propertySearchService);
        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        // Verify both requests were made
        $this->assertEquals(2, $requestsCount);
        $this->assertCount(2, $capturedRequests);
    }

    public function testUpdateField(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'john@example.com');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);

        $httpClient = new MockHttpClient([
            new MockResponse('john@example.com', ['http_code' => 200])
        ]);

        $httpRequestBuilderService = $this->createMock(HttpRequestBuilderService::class);
        $tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $urlBuilderService = $this->createMock(UrlBuilderService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);

        $service = new ApiCallsService($httpClient, $leadModel, $httpRequestBuilderService, $tokenReplacementService, $urlBuilderService, $propertySearchService);

        $response = $httpClient->request('GET', 'http://example.com');
        $service->updateField($leadEventLog, 'email', $response->getContent(), '');
    }

    public function testUpdateFieldWithRegex(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'john@example.com test@example.com');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);

        $httpClient = new MockHttpClient([
            new MockResponse('Email: john@example.com, Another: test@example.com', ['http_code' => 200])
        ]);

        $httpRequestBuilderService = $this->createMock(HttpRequestBuilderService::class);
        $tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $urlBuilderService = $this->createMock(UrlBuilderService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);

        $service = new ApiCallsService($httpClient, $leadModel, $httpRequestBuilderService, $tokenReplacementService, $urlBuilderService, $propertySearchService);

        $response = $httpClient->request('GET', 'http://example.com');
        $service->updateField($leadEventLog, 'email', $response->getContent(), '/[\w\.-]+@[\w\.-]+\.\w+/');
    }

    /**
     * @dataProvider errorStatusCodes
     */
    public function testCheckIfResponseValidThrowsExceptionForErrorCodes(int $statusCode): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('Error', ['http_code' => $statusCode])
        ]);

        $leadModel = $this->createMock(LeadModel::class);
        $httpRequestBuilderService = $this->createMock(HttpRequestBuilderService::class);
        $tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $urlBuilderService = $this->createMock(UrlBuilderService::class);
        $propertySearchService = $this->createMock(PropertySearchService::class);

        $service = new ApiCallsService($httpClient, $leadModel, $httpRequestBuilderService, $tokenReplacementService, $urlBuilderService, $propertySearchService);

        $mockResponse = $httpClient->request('GET', 'http://example.com');

        $this->expectException(\Exception::class);

        $service->checkIfResponseValid($mockResponse);
    }

    /**
     * @return array<string, array<int>>
     */
    public function errorStatusCodes(): array
    {
        return [
            'Bad Request' => [400],
            'Unauthorized' => [401],
            'Not Found' => [404],
            'Internal Server Error' => [500],
            'Bad Gateway' => [502],
        ];
    }
}