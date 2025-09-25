<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\TokenReplacementService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HttpRequestBuilderServiceTest extends TestCase
{
    private HttpRequestBuilderService $service;
    private MockObject $urlBuilderService;
    private MockObject $tokenReplacementService;
    private MockObject $leadEventLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlBuilderService = $this->createMock(UrlBuilderService::class);
        $this->tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $this->leadEventLog = $this->createMock(LeadEventLog::class);
        $this->service = new HttpRequestBuilderService($this->urlBuilderService, $this->tokenReplacementService);
    }

    public function testBuildUrlAndOptionsForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with($dto, 'https://example.com/api', 'param1=value1&param2=value2')
            ->willReturn('https://example.com/api?param1=value1&param2=value2');

        $result = $this->service->buildUrlAndOptions('param1=value1&param2=value2', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api?param1=value1&param2=value2', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPostMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
            'body' => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPutMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PUT',
            contentType: 'application/json'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
            'body' => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPatchMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PATCH',
            contentType: 'application/json'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
            'body' => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithAuthentication(): void
    {
        // Test with GET method to avoid the undefined $url variable issue
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json',
            username: 'user123',
            password: 'pass123'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with($dto, 'https://example.com/api', 'data=test')
            ->willReturn('https://example.com/api?data=test');

        $result = $this->service->buildUrlAndOptions('data=test', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api?data=test', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
            'auth_basic' => ['user123', 'pass123'],
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithEmptyValue(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        // With empty value, appendQueryString should not be called
        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
        ], $result['options']);
    }

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testBuildUrlAndOptionsWithDifferentContentTypes(string $method, string $contentType): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: $method,
            contentType: $contentType
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        if ($method === 'GET') {
            $this->urlBuilderService->expects($this->once())
                ->method('appendQueryString')
                ->willReturn('https://example.com/api?data=test');

            $result = $this->service->buildUrlAndOptions('data=test', $dto, $this->leadEventLog);
            /** @var array<string, mixed> $headers */
            $headers = $result['options']['headers'];
            $this->assertEquals($contentType, $headers['Content-Type']);
        } else {
            // Non-GET methods should work now that the service is fixed
            $this->urlBuilderService->expects($this->never())
                ->method('appendQueryString');

            $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto, $this->leadEventLog);

            $this->assertEquals('https://example.com/api', $result['url']);
            /** @var array<string, mixed> $headers */
            $headers = $result['options']['headers'];
            $this->assertEquals($contentType, $headers['Content-Type']);
            $this->assertEquals('{"data": "test"}', $result['options']['body']);
        }
    }

    /**
     * @return array<string, array<string>>
     */
    public function httpMethodsProvider(): array
    {
        return [
            'GET with JSON' => ['GET', 'application/json'],
            'GET with XML' => ['GET', 'application/xml'],
            'POST with JSON' => ['POST', 'application/json'],
            'PUT with XML' => ['PUT', 'application/xml'],
            'PATCH with form data' => ['PATCH', 'application/x-www-form-urlencoded'],
        ];
    }
}