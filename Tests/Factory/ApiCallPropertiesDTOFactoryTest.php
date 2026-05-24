<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Factory;

use MauticPlugin\LeuchtfeuerAPICallsBundle\Factory\ApiCallPropertiesDTOFactory;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApiCallPropertiesDTOFactoryTest extends TestCase
{
    /** @var CampaignActionSecretService&MockObject */
    private CampaignActionSecretService $secretService;

    private ApiCallPropertiesDTOFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretService = $this->createMock(CampaignActionSecretService::class);
        $this->factory         = new ApiCallPropertiesDTOFactory($this->secretService);
    }

    public function testCreateFromPropertiesDecryptsSecrets(): void
    {
        $this->secretService->expects(self::exactly(2))
            ->method('decryptIfNeeded')
            ->willReturnCallback(static fn (?string $value): string => match ($value) {
                'encrypted-password' => 'plain-password',
                'encrypted-header'   => 'Authorization: Bearer token',
                default              => '',
            });

        $dto = $this->factory->createFromProperties([
            'url'                   => 'https://api.example.com',
            'method'                => 'POST',
            'contentType'           => 'application/json',
            'password'              => 'encrypted-password',
            'authorization_header'  => 'encrypted-header',
        ]);

        self::assertSame('plain-password', $dto->password);
        self::assertSame('Authorization: Bearer token', $dto->authorizationHeader);
    }
}
