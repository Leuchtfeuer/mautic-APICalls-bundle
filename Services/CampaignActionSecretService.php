<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Mautic\CoreBundle\Helper\EncryptionHelper;

class CampaignActionSecretService
{
    public function __construct(
        private EncryptionHelper $encryptionHelper,
    ) {
    }

    public function encrypt(string $plaintext): string
    {
        return $this->encryptionHelper->encrypt($plaintext);
    }

    public function isEncrypted(?string $value): bool
    {
        if (null === $value || '' === $value) {
            return false;
        }

        $parts = explode('|', $value, 2);

        return 2 === count($parts) && '' !== $parts[0] && '' !== $parts[1];
    }

    public function hasStoredSecret(?string $value): bool
    {
        return null !== $value && '' !== $value;
    }

    public function sanitizeForFormDisplay(?string $value): string
    {
        return '';
    }

    public function encryptIfNeeded(?string $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return $this->encrypt($value);
    }

    public function decryptIfNeeded(?string $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (!$this->isEncrypted($value)) {
            return $value;
        }

        $decrypted = $this->encryptionHelper->decrypt($value);

        return false === $decrypted ? '' : (string) $decrypted;
    }
}
