<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\PropertySearchService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\TokenReplacementService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ApiCallsServiceMetadataTest extends TestCase
{
    /** @var LeadModel&MockObject */
    private LeadModel $leadModel;

    private ApiCallsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leadModel = $this->createMock(LeadModel::class);
        $this->service   = new ApiCallsService(
            new MockHttpClient(),
            $this->leadModel,
            $this->createMock(HttpRequestBuilderService::class),
            $this->createMock(TokenReplacementService::class),
            $this->createMock(UrlBuilderService::class),
            $this->createMock(PropertySearchService::class),
        );
    }

    public function testSetMetadataStoresFullBodyWhenWithinLimit(): void
    {
        $responseBody = '{"data": "test"}';
        $httpClient   = new MockHttpClient([
            new MockResponse($responseBody, [
                'http_code' => 200,
                'response_headers' => [
                    'Content-Type' => ['application/json'],
                ],
            ]),
        ]);

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->expects(self::once())
            ->method('setMetadata')
            ->with([
                'event' => 'api_calls',
                'object_description' => 'API-Request Response',
                'response_header' => [
                    'content-type' => ['application/json'],
                ],
                'response_body' => $responseBody,
                'method' => 'POST',
            ]);

        $response = $httpClient->request('GET', 'http://example.com');
        $this->service->setMetadata($leadEventLog, $response, 'POST');
    }

    public function testSetMetadataTruncatesLargeResponseBody(): void
    {
        $responseBody = str_repeat('a', ApiCallsService::MAX_METADATA_RESPONSE_BODY_LENGTH + 100);
        $httpClient   = new MockHttpClient([
            new MockResponse($responseBody, ['http_code' => 200]),
        ]);

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->expects(self::once())
            ->method('setMetadata')
            ->with(self::callback(static function (array $metadata) use ($responseBody): bool {
                return ApiCallsService::MAX_METADATA_RESPONSE_BODY_LENGTH === strlen($metadata['response_body'])
                    && str_starts_with($responseBody, $metadata['response_body'])
                    && true === $metadata['response_body_truncated']
                    && strlen($responseBody) === $metadata['response_body_original_length']
                    && 'api_calls' === $metadata['event']
                    && 'POST' === $metadata['method'];
            }));

        $response = $httpClient->request('GET', 'http://example.com');
        $this->service->setMetadata($leadEventLog, $response, 'POST');
    }
}
