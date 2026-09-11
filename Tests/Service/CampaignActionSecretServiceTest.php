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
    private MockObject $encryptionHelper;

    private CampaignActionSecretService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryptionHelper = $this->createMock(EncryptionHelper::class);
        $this->service          = new CampaignActionSecretService($this->encryptionHelper);
    }

    public function testEncryptIfNeededEncryptsPlaintext(): void
    {
        $this->encryptionHelper->expects($this->once())
            ->method('encrypt')
            ->with('secret')
            ->willReturn('cipher|vector');

        $this->assertSame('cipher|vector', $this->service->encryptIfNeeded('secret'));
    }

    public function testEncryptIfNeededKeepsEncryptedValue(): void
    {
        $this->encryptionHelper->expects($this->never())->method('encrypt');

        $this->assertSame('cipher|vector', $this->service->encryptIfNeeded('cipher|vector'));
    }

    public function testDecryptIfNeededDecryptsEncryptedValue(): void
    {
        $this->encryptionHelper->expects($this->once())
            ->method('decrypt')
            ->with('cipher|vector')
            ->willReturn('secret');

        $this->assertSame('secret', $this->service->decryptIfNeeded('cipher|vector'));
    }

    public function testDecryptIfNeededReturnsLegacyPlaintext(): void
    {
        $this->encryptionHelper->expects($this->never())->method('decrypt');

        $this->assertSame('legacy-secret', $this->service->decryptIfNeeded('legacy-secret'));
    }

    public function testSanitizeForFormDisplayClearsStoredSecrets(): void
    {
        $this->assertSame('', $this->service->sanitizeForFormDisplay('legacy-secret'));
        $this->assertSame('', $this->service->sanitizeForFormDisplay('cipher|vector'));
        $this->assertSame('', $this->service->sanitizeForFormDisplay(''));
        $this->assertSame('', $this->service->sanitizeForFormDisplay(null));
    }
}
