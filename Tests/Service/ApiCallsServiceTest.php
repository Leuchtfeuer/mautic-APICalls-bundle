<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use PHPUnit\Framework\TestCase;

class ApiCallsServiceTest extends TestCase
{

    private ApiCallsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiCallsService();
    }


    public function testcheckIfWeGetCorrectBodyValueArray():void {

        $this->assertEquals($this->getAwaitedResult(), $this->service->buildBodyValueArrayForApiRequest($this->getTokenValue(), $this->getTokens(), 'POST', 'test.com'));
    }

    /**
     * @return array<mixed>
     */
    public function getTokenValue():string
    {
         return "testName testEmail";
    }

    public function getTokens():string
    {
        return "{contactfield=firstname} {contactfield=email}";
    }


    /**
     * @return array<mixed>
     */
    public function getAwaitedResult():array
    {
        return  [
            'url' => 'test.com',
            'firstname' => 'testName',
            'email' => 'testEmail',
            'methode' => 'POST'
        ];
    }



}