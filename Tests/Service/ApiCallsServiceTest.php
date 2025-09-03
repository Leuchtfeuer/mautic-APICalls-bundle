<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;


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



}