<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CampaignActionSecretServiceTest extends TestCase
{
    /** @var EncryptionHelper&MockObject */
    private EncryptionHelper $encryptionHelper;

    private CampaignActionSecretService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryptionHelper = $this->createMock(EncryptionHelper::class);
        $this->service          = new CampaignActionSecretService($this->encryptionHelper);
    }

    public function testEncryptIfNeededEncryptsPlaintext(): void
    {
        $this->encryptionHelper->expects(self::once())
            ->method('encrypt')
            ->with('secret')
            ->willReturn('cipher|vector');

        self::assertSame('cipher|vector', $this->service->encryptIfNeeded('secret'));
    }

    public function testEncryptIfNeededKeepsEncryptedValue(): void
    {
        $this->encryptionHelper->expects(self::never())->method('encrypt');

        self::assertSame('cipher|vector', $this->service->encryptIfNeeded('cipher|vector'));
    }

    public function testDecryptIfNeededDecryptsEncryptedValue(): void
    {
        $this->encryptionHelper->expects(self::once())
            ->method('decrypt')
            ->with('cipher|vector')
            ->willReturn('secret');

        self::assertSame('secret', $this->service->decryptIfNeeded('cipher|vector'));
    }

    public function testDecryptIfNeededReturnsLegacyPlaintext(): void
    {
        $this->encryptionHelper->expects(self::never())->method('decrypt');

        self::assertSame('legacy-secret', $this->service->decryptIfNeeded('legacy-secret'));
    }

    public function testSanitizeForFormDisplayClearsStoredSecrets(): void
    {
        self::assertSame('', $this->service->sanitizeForFormDisplay('legacy-secret'));
        self::assertSame('', $this->service->sanitizeForFormDisplay('cipher|vector'));
        self::assertSame('', $this->service->sanitizeForFormDisplay(''));
        self::assertSame('', $this->service->sanitizeForFormDisplay(null));
    }
}
