<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\DTO\ApiCallPropertiesDTO;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ApiCallsServiceTest extends MauticMysqlTestCase
{
    private function createMockLeadEventLog(): LeadEventLog
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getProfileFields')->willReturn(['firstname' => 'John', 'email' => 'john@example.com']);

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        return $leadEventLog;
    }

    public function testSendRequestWithBasicAuth(): void
    {
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedOptions) {
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}',
            urlParameters: null,
            username: 'user',
            password: 'pass',
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        $this->assertArrayHasKey('auth_basic', $capturedOptions);
        $this->assertEquals(['user', 'pass'], $capturedOptions['auth_basic']);
        $this->assertEquals('LeuchtfeuerMauticAPI/1.0', $capturedOptions['headers']['User-Agent']);
        $this->assertEquals('application/json', $capturedOptions['headers']['Content-Type']);
        $this->assertEquals('{"test": "data"}', $capturedOptions['body']);
    }

    public function testSendRequestWithoutAuth(): void
    {
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedOptions) {
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}',
            urlParameters: null,
            username: null,
            password: null,
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        $this->assertArrayNotHasKey('auth_basic', $capturedOptions);
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

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestsCount, &$capturedRequests, $redirectResponse, $finalResponse) {
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

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: 'application/json',
            body: '{"test": "data"}',
            urlParameters: null,
            username: 'user',
            password: 'pass',
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        $this->assertEquals(2, $requestsCount);
        $this->assertCount(2, $capturedRequests);

        $this->assertEquals('POST', $capturedRequests[0]['method']);
        $this->assertEquals('https://api.example.com/webhook', $capturedRequests[0]['url']);
        $this->assertEquals('{"test": "data"}', $capturedRequests[0]['options']['body']);

        $this->assertEquals('POST', $capturedRequests[1]['method']);
        $this->assertEquals('https://api.example.com/redirected', $capturedRequests[1]['url']);
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

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: $method,
            contentType: 'application/json',
            body: '{"test": "data"}',
            urlParameters: null,
            username: null,
            password: null,
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

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

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'POST',
            contentType: $contentType,
            body: 'test data',
            urlParameters: null,
            username: null,
            password: null,
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        $this->assertArrayHasKey('headers', $capturedOptions);
        $this->assertArrayHasKey('Content-Type', $capturedOptions['headers']);
        $this->assertEquals($contentType, $capturedOptions['headers']['Content-Type']);
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

    public function testSendRequestWithGetMethod(): void
    {
        $capturedUrl = null;
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$capturedUrl, &$capturedOptions) {
            $capturedUrl = $url;
            $capturedOptions = $options;
            return new MockResponse('success', ['http_code' => 200]);
        });

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $dto = new ApiCallPropertiesDTO(
            url: 'https://api.example.com/webhook',
            method: 'GET',
            contentType: 'application/json',
            body: null,
            urlParameters: 'param1=value1&param2=value2',
            username: null,
            password: null,
            contactField: null,
            regex: null
        );

        $lead = $this->createMockLeadEventLog();

        $service->sendRequest($lead, $dto);

        $this->assertEquals('https://api.example.com/webhook?param1=value1&param2=value2', $capturedUrl);
        $this->assertArrayNotHasKey('body', $capturedOptions);
    }

    public function testUpdateField(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'john@example.com');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);

        $httpClient = new MockHttpClient([
            new MockResponse('john@example.com', ['http_code' => 200])
        ]);

        $service = new ApiCallsService($httpClient, $leadModel);

        $response = $httpClient->request('GET', 'http://example.com');
        $service->updateField($leadEventLog, 'email', $response, '');
    }

    public function testUpdateFieldWithRegex(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'john@example.com test@example.com');

        $leadEventLog = $this->createMock(LeadEventLog::class);
        $leadEventLog->method('getLead')->willReturn($lead);

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->once())
            ->method('saveEntity')
            ->with($lead);

        $httpClient = new MockHttpClient([
            new MockResponse('Email: john@example.com, Another: test@example.com', ['http_code' => 200])
        ]);

        $service = new ApiCallsService($httpClient, $leadModel);

        $response = $httpClient->request('GET', 'http://example.com');
        $service->updateField($leadEventLog, 'email', $response, '/[\w\.-]+@[\w\.-]+\.\w+/');
    }

    /**
     * @dataProvider errorStatusCodes
     */
    public function testCheckIfResponseValidThrowsExceptionForErrorCodes(int $statusCode): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('Error', ['http_code' => $statusCode])
        ]);

        $leadModel = $this->createMock(LeadModel::class);
        $service = new ApiCallsService($httpClient, $leadModel);

        $mockResponse = $httpClient->request('GET', 'http://example.com');

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