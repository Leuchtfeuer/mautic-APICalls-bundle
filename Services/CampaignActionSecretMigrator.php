<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Event;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;

class CampaignActionSecretMigrator
{
    private const SECRET_FIELDS = ['password', 'authorization_header'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CampaignActionSecretService $secretService,
    ) {
    }

    public function hasPendingSecrets(): bool
    {
        foreach ($this->findApiRequestEvents() as $event) {
            if (self::containsPlaintextSecrets($event->getProperties())) {
                return true;
            }
        }

        return false;
    }

    public function migrate(): int
    {
        $updated = 0;

        foreach ($this->findApiRequestEvents() as $event) {
            $properties = $event->getProperties();

            if (!is_array($properties)) {
                continue;
            }

            $encrypted = $this->encryptPlaintextSecrets($properties);

            if ($encrypted === $properties) {
                continue;
            }

            $event->setProperties($encrypted);
            $this->entityManager->persist($event);
            ++$updated;
        }

        if ($updated > 0) {
            $this->entityManager->flush();
        }

        return $updated;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function containsPlaintextSecrets(array $properties): bool
    {
        foreach (self::SECRET_FIELDS as $field) {
            $value = $properties[$field] ?? null;

            if (!is_string($value) || '' === $value) {
                continue;
            }

            if (!self::looksEncrypted($value)) {
                return true;
            }
        }

        return false;
    }

    public static function looksEncrypted(string $value): bool
    {
        $parts = explode('|', $value, 2);

        return 2 === count($parts) && '' !== $parts[0] && '' !== $parts[1];
    }

    /**
     * @return Event[]
     */
    private function findApiRequestEvents(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Event::class, 'e')
            ->where('e.type = :type')
            ->andWhere('e.deleted IS NULL')
            ->setParameter('type', CampaignActionSubscriber::ACTION_TYPE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    private function encryptPlaintextSecrets(array $properties): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (!isset($properties[$field]) || !is_string($properties[$field])) {
                continue;
            }

            $properties[$field] = $this->secretService->encryptIfNeeded($properties[$field]);
        }

        return $properties;
    }
}
