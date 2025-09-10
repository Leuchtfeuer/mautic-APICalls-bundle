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
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedOptions) {
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            'user',
            'pass'
        );

        $this->assertContains('User-Agent: LeuchtfeuerMauticAPI/1.0', $capturedOptions['headers']);
        $this->assertContains('Content-Type: application/json', $capturedOptions['headers']);
        $this->assertContains('Authorization: Basic ' . base64_encode('user:pass'), $capturedOptions['headers']);
        $this->assertEquals('{"test": "data"}', $capturedOptions['body']);
    }

    public function testSendRequestWithoutAuth(): void
    {
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedOptions) {
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            '',
            ''
        );

        $this->assertNotContains('Authorization: Basic ' . base64_encode('user:pass'), $capturedOptions['headers']);
    }

    public function testSendRequestHandlesRedirectsCorrectly(): void
    {
        $redirectResponse = new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['Location' => 'https://api.example.com/redirected']
        ]);
        $finalResponse = new MockResponse('success', ['http_code' => 200]);

        $requestsCount = 0;
        $capturedRequests = [];

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestsCount, &$capturedRequests, $redirectResponse,
            $finalResponse) {
            $capturedRequests[] = [
                'method' => $method,
                'url' => $url,
                'options' => $options
            ];
            $requestsCount++;

            if ($requestsCount === 1) {
                return $redirectResponse;
            }
            return $finalResponse;
        });

        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            'POST',
            'application/json',
            'user',
            'pass'
        );

        // Verify two requests were made
        $this->assertEquals(2, $requestsCount);
        $this->assertCount(2, $capturedRequests);

        // Verify first request (original URL)
        $this->assertEquals('POST', $capturedRequests[0]['method']);
        $this->assertEquals('https://api.example.com/webhook', $capturedRequests[0]['url']);
        $this->assertEquals('{"test": "data"}', $capturedRequests[0]['options']['body']);

        // Verify second request (redirected URL) - body and auth should be preserved
        /** @phpstan-ignore-next-line */
        $this->assertEquals('POST', $capturedRequests[1]['method']);
        /** @phpstan-ignore-next-line */
        $this->assertEquals('https://api.example.com/redirected', $capturedRequests[1]['url']);
        /** @phpstan-ignore-next-line */
        $this->assertEquals('{"test": "data"}', $capturedRequests[1]['options']['body']);
    }

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testSendRequestWithDifferentHttpMethods(string $method): void
    {
        $capturedMethod = null;

        $httpClient = new MockHttpClient(function ($requestMethod, $url, $options) use (&$capturedMethod) {
            $capturedMethod = $requestMethod;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            '{"test": "data"}',
            'https://api.example.com/webhook',
            $method,
            'application/json',
            '',
            ''
        );

        $this->assertEquals($method, $capturedMethod);
    }

    /**
     * @return array<string, array<string>>
     */
    public function httpMethodsProvider(): array
    {
        return [
            'POST method' => ['POST'],
            'PUT method' => ['PUT'],
            'PATCH method' => ['PATCH'],
        ];
    }

    /**
     * @dataProvider contentTypesProvider
     */
    public function testSendRequestWithDifferentContentTypes(string $contentType): void
    {
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedOptions) {
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $service = new ApiCallsService($httpClient);

        $service->sendRequest(
            'test data',
            'https://api.example.com/webhook',
            'POST',
            $contentType,
            '',
            ''
        );

        $this->assertContains('Content-Type: ' . $contentType, $capturedOptions['headers']);
        $this->assertEquals('test data', $capturedOptions['body']);
    }

    /**
     * @return array<string, array<string>>
     */
    public function contentTypesProvider(): array
    {
        return [
            'JSON content type' => ['application/json'],
            'XML content type' => ['application/xml'],
            'Form URL Encoded content type' => ['application/x-www-form-urlencoded'],
            'Plain text content type' => ['text/plain'],
        ];
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
     * @return array<string, array<int>>
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