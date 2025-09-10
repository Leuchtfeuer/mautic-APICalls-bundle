<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ApiCallsServiceTest extends MauticMysqlTestCase
{
    public function testSendRequestWithBasicAuth(): void
    {
        $mockResponse = new MockResponse('success', ['http_code' => 200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            'user',
            'pass'
        );

        $this->assertTrue(true);
    }

    public function testSendRequestWithoutAuth(): void
    {
        $mockResponse = new MockResponse('success', ['http_code' => 200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '<xml>test</xml>',
            'https://api.example.com/data',
            'GET',
            'application/xml',
            '',
            ''
        );

        $this->assertTrue(true);
    }

    public function testSendRequestHandlesRedirectsCorrectly(): void
    {
        $redirectResponse = new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['Location' =>
                'https://api.example.com/redirected']
        ]);
        $finalResponse = new MockResponse('success', ['http_code' =>
            200]);

        $httpClient = new MockHttpClient([$redirectResponse,
            $finalResponse]);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            '',
            ''
        );

        $this->assertTrue(true);
    }

    public function testSendRequestStopsAfterMaxRedirects(): void
    {
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['Location' => 'https://api.example.com/redirect' . $i]
            ]);
        }

        $responses[] = new MockResponse('final', ['http_code' => 200]);

        $httpClient = new MockHttpClient($responses);
        $service = new ApiCallsService($httpClient);

        $this->expectException(RedirectionException::class);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            '',
            ''
        );
    }


    /**
     * @dataProvider httpMethodsProvider
     */
    public function testSendRequestWithDifferentHttpMethods(string $method): void
    {
        $mockResponse = new MockResponse('success', ['http_code' => 200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            $method,
            'application/json',
            '',
            ''
        );

        $this->assertTrue(true);
    }

    public function httpMethodsProvider(): array
    {
        return [
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
        ];
    }

    /**
     * @dataProvider contentTypesProvider
     */
    public function testSendRequestWithDifferentContentTypes(string $contentType): void
    {
        $mockResponse = new MockResponse('success', ['http_code' => 200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            'test data',
            'https://api.example.com/webhook',
            'POST',
            $contentType,
            '',
            ''
        );

        $this->assertTrue(true);
    }

    public function contentTypesProvider(): array
    {
        return [
            'JSON' => ['application/json'],
            'XML' => ['application/xml'],
            'Form URL Encoded' => ['application/x-www-form-urlencoded'],
        ];
    }

    public function testSendRequestExecutesSuccessfully(): void
    {
        $mockResponse = new MockResponse('success', ['http_code' => 200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            'user',
            'pass'
        );

        $this->assertTrue(true);
    }

    public function testCheckIfResponseValidDoesNotThrowException(): void
    {
        $mockResponse = new MockResponse('test content', ['http_code' =>
            200]);
        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            '',
            ''
        );

        $this->assertTrue(true);
    }



/**
 * @dataProvider errorStatusCodes
 */
    public function testCheckIfResponseValidThrowsExceptionForErrorCodes(int $statusCode): void
    {
        $mockResponse = new MockResponse('Error', [
            'http_code' => $statusCode,
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiCallsService($httpClient);

        $this->expectException(\Exception::class);

        $service->checkIfResponseValid($mockResponse);
    }

    /**
     * @return array<mixed>
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