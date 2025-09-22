<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\HttpRequestBuilderService;
use PHPUnit\Framework\TestCase;

class HttpRequestBuilderServiceTest extends TestCase
{
    private HttpRequestBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HttpRequestBuilderService();
    }

    public function testBuildUrlAndOptionsForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

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

    public function testBuildUrlAndOptionsForGetRequestWithExistingQuery(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api?existing=param',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $this->service->buildUrlAndOptions('new=param', $dto);

        $this->assertEquals('https://example.com/api?existing=param&new=param', $result['url']);
    }

    public function testBuildUrlAndOptionsForPostRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json'
        );

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto);

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
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            username: 'user123',
            password: 'pass123'
        );

        $result = $this->service->buildUrlAndOptions('{"data": "test"}', $dto);

        $this->assertEquals([
            'headers' => [
                'User-Agent' => 'LeuchtfeuerMauticAPI/1.0',
                'Content-Type' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => true,
            'max_redirects' => 0,
            'body' => '{"data": "test"}',
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

        $result = $this->service->buildUrlAndOptions('', $dto);

        $this->assertEquals('https://example.com/api', $result['url']);
    }
}