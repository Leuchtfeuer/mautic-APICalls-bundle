<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Aws\Api\Service;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiCallsServiceTest extends TestCase
{

    private ApiCallsService $apiCallService;

    // normalizeUrl-Test
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiCallService = $this->getMockBuilder(ApiCallsService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider urlProvider
     */
    public function testNormalizeUrlWithDataProvider(string $input, string $expected): void
    {
        $result = $this->apiCallService->normalizeUrl($input);
        $this->assertEquals($expected, $result);
    }

    public function urlProvider(): array
    {
        return [
            ['example.com', 'https://example.com'],
            ['http://example.com', 'http://example.com'],
            ['https://example.com', 'https://example.com'],
            ['invalid-url!', 'https://invalid-url!'],
            ['', 'https://'],
        ];
    }

    // sendRequest-Test
    public function testSendRequestCallsHttpClient(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://someapi.com',
                $this->callback(fn($options) =>
                    $options['body'] === "testName testEmail"
                    && $options['headers']['User-Agent'] === 'LeuchtfeuerMauticAPI/1.0'
                    && $options['headers']['Content-Type'] === 'text/plain'
                )
            )
            ->willReturn($responseMock);

        $service = new ApiCallsService($httpClientMock);

        $this->assertEquals(
            Response::HTTP_OK,
            $service->sendRequest('testName testEmail', 'https://someapi.com', 'POST')
        );
    }

}