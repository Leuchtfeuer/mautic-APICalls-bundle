<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class ApiCallsServiceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

    }

    /**
     * @dataProvider errorStatusCodes
     */
    public function testCheckStatusCodeThrowsExceptionForErrorCodes(int $statusCode): void
    {
        $mockResponse = new MockResponse('Error', [
            'http_code' => $statusCode,
        ]);

        $service = new ApiCallsService(new MockHttpClient());

        $this->expectException(\Exception::class);

        $service->checkStatusCode($mockResponse);
    }


    /**
     * @return array<mixed>
     */
    public function errorStatusCodes(): array
    {
        return [
            'Redirect' => [300],
            'Bad Request' => [400],
            'Unauthorized' => [401],
            'Not Found' => [404],
            'Internal Server Error' => [500],
            'Bad Gateway' => [502],
        ];
    }


    public function testSendRequestSendsJsonWithCorrectContent(): void
    {
        $testValue = 'Hello World';
        $testUrl = 'https://example.com/api';
        $testMethod = 'POST';

        $responseMock = $this->createMock(ResponseInterface::class);

        $capturedOptions = null;

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->willReturnCallback(function($method, $url, $options) use (&$capturedOptions, $responseMock) {
                $capturedOptions = $options;
                return $responseMock;
            });

        $service = new ApiCallsService($httpClientMock);
        $service->sendRequest($testValue, $testUrl, $testMethod);

        $this->assertArrayHasKey('json', $capturedOptions);

        $this->assertSame($testValue, $capturedOptions['json']);

        $jsonString = json_encode($capturedOptions['json']);
        $this->assertJson($jsonString);
        $this->assertSame(json_encode($testValue), $jsonString);
    }





}