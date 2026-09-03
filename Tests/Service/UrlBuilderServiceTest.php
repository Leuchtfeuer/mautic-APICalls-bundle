<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\UrlBuilderService;
use PHPUnit\Framework\TestCase;

final class UrlBuilderServiceTest extends TestCase
{
    private UrlBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UrlBuilderService();
    }

    public function testAppendQueryStringForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $this->service->appendQueryString($dto, 'https://example.com/api', 'param1=value1&param2=value2');

        $this->assertSame('https://example.com/api?param1=value1&param2=value2', $result);
    }

    public function testAppendQueryStringWithExistingQuery(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $this->service->appendQueryString($dto, 'https://example.com/api?existing=param', 'new=param');

        $this->assertSame('https://example.com/api?existing=param&new=param', $result);
    }

    public function testAppendQueryStringForNonGetMethod(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json'
        );

        $result = $this->service->appendQueryString($dto, 'https://example.com/api', 'param1=value1');

        $this->assertSame('https://example.com/api', $result);
    }

    public function testAppendQueryStringWithEmptyValue(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $this->service->appendQueryString($dto, 'https://example.com/api', '');

        $this->assertSame('https://example.com/api', $result);
    }

    public function testAppendQueryStringWithSpecialCharacters(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $this->service->appendQueryString($dto, 'https://example.com/api', 'email=test@example.com&title=Hello World');

        $this->assertSame('https://example.com/api?email=test%40example.com&title=Hello+World', $result);
    }
}
