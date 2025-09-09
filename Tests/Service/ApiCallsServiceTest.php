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
    public function testCheckIfResponseValidThrowsExceptionForErrorCodes(int $statusCode): void
    {
        $mockResponse = new MockResponse('Error', [
            'http_code' => $statusCode,
        ]);

        $service = new ApiCallsService(new MockHttpClient());

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