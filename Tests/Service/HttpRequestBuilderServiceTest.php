<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\TokenReplacementService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class HttpRequestBuilderServiceTest extends TestCase
{
    private HttpRequestBuilderService $service;
    /**
     * @var MockObject&UrlBuilderService
     */
    private MockObject $urlBuilderService;
    /**
     * @var MockObject&TokenReplacementService
     */
    private MockObject $tokenReplacementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlBuilderService       = $this->createMock(UrlBuilderService::class);
        $this->tokenReplacementService = $this->createMock(TokenReplacementService::class);
        $this->service                 = new HttpRequestBuilderService($this->urlBuilderService, $this->tokenReplacementService);
    }

    public function testBuildUrlAndOptionsForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json',
            urlParameters: 'param1=value1&param2=value2'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with('https://example.com/api', 'param1=value1&param2=value2')
            ->willReturn('https://example.com/api?param1=value1&param2=value2');

        $result = $this->service->buildUrlAndOptions('', 'param1=value1&param2=value2', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api?param1=value1&param2=value2', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
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
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPutMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PUT',
            contentType: 'application/json'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPatchMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PATCH',
            contentType: 'application/json'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsForPostMethodWithUrlParameters(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            urlParameters: 'filter=active&page=1'
        );

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($this->leadEventLog, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with('https://example.com/api', 'filter=active&page=1')
            ->willReturn('https://example.com/api?filter=active&page=1');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', 'filter=active&page=1', $dto, $this->leadEventLog);

        $this->assertEquals('https://example.com/api?filter=active&page=1', $result['url']);
        $this->assertEquals('{"data": "test"}', $result['options']['body']);
    }

    public function testBuildUrlAndOptionsWithAuthentication(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json',
            urlParameters: 'data=test',
            username: 'user123',
            password: 'pass123'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with('https://example.com/api', 'data=test')
            ->willReturn('https://example.com/api?data=test');

        $result = $this->service->buildUrlAndOptions('', 'data=test', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api?data=test', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'auth_basic'    => ['user123', 'pass123'],
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithEmptyValue(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $result = $this->service->buildUrlAndOptions('', '', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
        ], $result['options']);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testBuildUrlAndOptionsWithDifferentContentTypes(string $method, string $contentType): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: $method,
            contentType: $contentType
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        if ('GET' === $method) {
            $this->urlBuilderService->expects($this->once())
                ->method('appendQueryString')
                ->willReturn('https://example.com/api?data=test');

            $result = $this->service->buildUrlAndOptions('', 'data=test', $dto, $leadEventLogStub);
            /** @var array<string, mixed> $headers */
            $headers = $result['options']['headers'];
            $this->assertEquals($contentType, $headers['Content-Type']);
        } else {
            $this->urlBuilderService->expects($this->never())
                ->method('appendQueryString');

            $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

            $this->assertEquals('https://example.com/api', $result['url']);
            /** @var array<string, mixed> $headers */
            $headers = $result['options']['headers'];
            $this->assertEquals($contentType, $headers['Content-Type']);
            $this->assertEquals('{"data": "test"}', $result['options']['body']);
        }
    }

    public function testBuildUrlAndOptionsWithAuthorizationHeader(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            authorizationHeader: 'Authorization: Bearer eyJhbGc123'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals('https://example.com/api', $result['url']);
        $this->assertEquals([
            'headers' => [
                'User-Agent'    => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer eyJhbGc123',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithCustomAuthorizationHeader(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            authorizationHeader: 'X-API-Key: secret123'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
                'X-API-Key'    => 'secret123',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithInvalidAuthorizationHeader(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            authorizationHeader: 'InvalidHeaderWithoutColon'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals([
            'headers' => [
                'User-Agent'   => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
        ], $result['options']);
    }

    public function testBuildUrlAndOptionsWithAuthenticationAndAuthorizationHeader(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            username: 'user123',
            password: 'pass123',
            authorizationHeader: 'X-Custom-Auth: token123'
        );
        $leadEventLogStub = $this->createStub(LeadEventLog::class);

        $this->tokenReplacementService->expects($this->once())
            ->method('getTokenizedUrl')
            ->with($leadEventLogStub, 'https://example.com/api')
            ->willReturn('https://example.com/api');

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', '', $dto, $leadEventLogStub);

        $this->assertEquals([
            'headers' => [
                'User-Agent'    => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type'  => 'application/json',
                'X-Custom-Auth' => 'token123',
            ],
            'verify_peer'   => true,
            'verify_host'   => true,
            'max_redirects' => 0,
            'body'          => '{"data": "test"}',
            'auth_basic'    => ['user123', 'pass123'],
        ], $result['options']);
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function httpMethodsProvider(): \Iterator
    {
        yield 'GET with JSON' => ['GET', 'application/json'];
        yield 'GET with XML' => ['GET', 'application/xml'];
        yield 'POST with JSON' => ['POST', 'application/json'];
        yield 'PUT with XML' => ['PUT', 'application/xml'];
        yield 'PATCH with form data' => ['PATCH', 'application/x-www-form-urlencoded'];
    }
}
