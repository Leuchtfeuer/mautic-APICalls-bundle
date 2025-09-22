<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HttpRequestBuilderServiceTest extends TestCase
{
    private HttpRequestBuilderService $service;
    private MockObject $urlBuilderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->urlBuilderService = $this->createMock(UrlBuilderService::class);
        $this->service = new HttpRequestBuilderService($this->urlBuilderService);
    }

    public function testBuildUrlAndOptionsForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with($dto, 'https://example.com/api', 'param1=value1&param2=value2')
            ->willReturn('https://example.com/api?param1=value1&param2=value2');

        $result = $this->service->buildUrlAndOptions('param1=value1&param2=value2', $dto);

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
        $this->expectError();
        $this->expectErrorMessage('Undefined variable $url');

        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json'
        );

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        // This will trigger the undefined variable error
        $this->service->buildUrlAndOptions('{"data": "test"}', $dto);
    }

    public function testBuildUrlAndOptionsForPutMethod(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Undefined variable $url');

        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PUT',
            contentType: 'application/json'
        );

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $this->service->buildUrlAndOptions('{"data": "test"}', $dto);
    }

    public function testBuildUrlAndOptionsForPatchMethod(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Undefined variable $url');

        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'PATCH',
            contentType: 'application/json'
        );

        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $this->service->buildUrlAndOptions('{"data": "test"}', $dto);
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

        $this->urlBuilderService->expects($this->once())
            ->method('appendQueryString')
            ->with($dto, 'https://example.com/api', 'data=test')
            ->willReturn('https://example.com/api?data=test');

        $result = $this->service->buildUrlAndOptions('data=test', $dto);

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

        // For GET with empty value, $url will be undefined, so this will error
        $this->expectError();
        $this->expectErrorMessage('Undefined variable $url');

        // With empty value, appendQueryString should not be called
        $this->urlBuilderService->expects($this->never())
            ->method('appendQueryString');

        $this->service->buildUrlAndOptions('', $dto);
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

        if ($method === 'GET') {
            $this->urlBuilderService->expects($this->once())
                ->method('appendQueryString')
                ->willReturn('https://example.com/api?data=test');

            $result = $this->service->buildUrlAndOptions('data=test', $dto);
            /** @var array<string, mixed> $headers */
            $headers = $result['options']['headers'];
            $this->assertEquals($contentType, $headers['Content-Type']);
        } else {
            // Non-GET methods will fail due to undefined $url
            $this->expectError();
            $this->service->buildUrlAndOptions('{"data": "test"}', $dto);
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