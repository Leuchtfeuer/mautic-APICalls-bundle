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


}