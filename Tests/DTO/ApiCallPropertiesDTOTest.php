<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\DTO;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use PHPUnit\Framework\TestCase;

class ApiCallPropertiesDTOTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}',
            urlParameters: 'param=value',
            username: 'user123',
            password: 'pass123',
            contactField: 'email',
            regex: '/test/'
        );

        $this->assertEquals('https://example.com/api', $dto->url);
        $this->assertEquals('POST', $dto->method);
        $this->assertEquals('application/json', $dto->contentType);
        $this->assertEquals('{"test": "data"}', $dto->body);
        $this->assertEquals('param=value', $dto->urlParameters);
        $this->assertEquals('user123', $dto->username);
        $this->assertEquals('pass123', $dto->password);
        $this->assertEquals('email', $dto->contactField);
        $this->assertEquals('/test/', $dto->regex);
    }

    public function testConstructorWithRequiredParametersOnly(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/xml'
        );

        $this->assertEquals('https://example.com/api', $dto->url);
        $this->assertEquals('GET', $dto->method);
        $this->assertEquals('application/xml', $dto->contentType);
        $this->assertEmpty($dto->body);
        $this->assertEmpty($dto->urlParameters);
        $this->assertEmpty($dto->username);
        $this->assertEmpty($dto->password);
        $this->assertEmpty($dto->contactField);
        $this->assertEmpty($dto->regex);
    }

    public function testBuildUrlAndOptionsForGetRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $dto->buildUrlAndOptions('param1=value1&param2=value2');

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

        $result = $dto->buildUrlAndOptions('new=param');

        $this->assertEquals('https://example.com/api?existing=param&new=param', $result['url']);
    }

    public function testBuildUrlAndOptionsForPostRequest(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'POST',
            contentType: 'application/json'
        );

        $result = $dto->buildUrlAndOptions('{"data": "test"}');

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

        $result = $dto->buildUrlAndOptions('{"data": "test"}');

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

        // Mock TokenHelper static method
        $this->assertEquals('name=John', $dto->getTokenizedValue($lead));
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

        // Mock TokenHelper static method
        $this->assertEquals('{"name": "John"}', $dto->getTokenizedValue($lead));
    }

    public function testBuildUrlAndOptionsWithEmptyValue(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://example.com/api',
            method: 'GET',
            contentType: 'application/json'
        );

        $result = $dto->buildUrlAndOptions('');

        $this->assertEquals('https://example.com/api', $result['url']);
    }
}