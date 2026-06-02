<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Functional\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretMigrator;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\Assert;

final class CampaignActionSecretMigrationFunctionalTest extends MauticMysqlTestCase
{
    private function createMigrator(): CampaignActionSecretMigrator
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = static::getContainer()->get(EncryptionHelper::class);

        return new CampaignActionSecretMigrator(
            $entityManager,
            new CampaignActionSecretService($encryptionHelper)
        );
    }

    private function createSecretService(): CampaignActionSecretService
    {
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = static::getContainer()->get(EncryptionHelper::class);

        return new CampaignActionSecretService($encryptionHelper);
    }

    public function testMigratorEncryptsPlaintextCampaignActionSecrets(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $campaign = new Campaign();
        $campaign->setName('API Calls migration test');
        $campaign->setIsPublished(true);

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send API Request');
        $event->setType(CampaignActionSubscriber::ACTION_TYPE);
        $event->setEventType('action');
        $event->setProperties([
            'url'                  => 'https://api.example.com/contacts',
            'method'               => 'POST',
            'contentType'          => 'application/json',
            'body'                 => '{"email":"test@example.com"}',
            'password'             => 'legacy-plain-password',
            'authorization_header' => 'Authorization: Bearer legacy-token',
        ]);

        $entityManager->persist($campaign);
        $entityManager->persist($event);
        $entityManager->flush();
        $eventId = (int) $event->getId();
        $entityManager->clear();

        $migrator = $this->createMigrator();

        Assert::assertTrue($migrator->hasPendingSecrets());
        Assert::assertSame(1, $migrator->migrate());
        Assert::assertFalse($migrator->hasPendingSecrets());

        $reloaded = $entityManager->find(Event::class, $eventId);
        Assert::assertInstanceOf(Event::class, $reloaded);

        $properties    = $reloaded->getProperties();
        $secretService = $this->createSecretService();

        Assert::assertTrue($secretService->isEncrypted($properties['password']));
        Assert::assertTrue($secretService->isEncrypted($properties['authorization_header']));
        Assert::assertSame('legacy-plain-password', $secretService->decryptIfNeeded($properties['password']));
        Assert::assertSame('Authorization: Bearer legacy-token', $secretService->decryptIfNeeded($properties['authorization_header']));
    }
}
