<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\DTO;

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
            regex: '/test/',
            objectKey: 'data',
            valueKey: 'user.email',
            authorizationHeader: 'Authorization: Bearer token123'
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
        $this->assertEquals('data', $dto->objectKey);
        $this->assertEquals('user.email', $dto->valueKey);
        $this->assertEquals('Authorization: Bearer token123', $dto->authorizationHeader);
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
        $this->assertEmpty($dto->objectKey);
        $this->assertEmpty($dto->valueKey);
        $this->assertEmpty($dto->authorizationHeader);
    }

    public function testConstructorWithAuthorizationHeaderOnly(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com',
            method: 'POST',
            contentType: 'application/json',
            authorizationHeader: 'X-API-Key: secret123'
        );

        $this->assertEquals('https://api.example.com', $dto->url);
        $this->assertEquals('POST', $dto->method);
        $this->assertEquals('application/json', $dto->contentType);
        $this->assertEquals('X-API-Key: secret123', $dto->authorizationHeader);
        $this->assertEmpty($dto->username);
        $this->assertEmpty($dto->password);
    }

    public function testConstructorWithJsonExtraction(): void
    {
        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/user',
            method: 'GET',
            contentType: 'application/json',
            contactField: 'email',
            objectKey: 'user',
            valueKey: 'email'
        );

        $this->assertEquals('https://api.example.com/user', $dto->url);
        $this->assertEquals('GET', $dto->method);
        $this->assertEquals('application/json', $dto->contentType);
        $this->assertEquals('email', $dto->contactField);
        $this->assertEquals('user', $dto->objectKey);
        $this->assertEquals('email', $dto->valueKey);
    }
}
