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

final class CampaignActionSecretMigrationFunctionalTest extends MauticMysqlTestCase
{
    private function createMigrator(): CampaignActionSecretMigrator
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = self::getContainer()->get(EncryptionHelper::class);

        return new CampaignActionSecretMigrator(
            $entityManager,
            new CampaignActionSecretService($encryptionHelper)
        );
    }

    private function createSecretService(): CampaignActionSecretService
    {
        /** @var EncryptionHelper $encryptionHelper */
        $encryptionHelper = self::getContainer()->get(EncryptionHelper::class);

        return new CampaignActionSecretService($encryptionHelper);
    }

    public function testMigratorEncryptsPlaintextCampaignActionSecrets(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

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

        $this->assertTrue($migrator->hasPendingSecrets());
        $this->assertSame(1, $migrator->migrate());
        $this->assertFalse($migrator->hasPendingSecrets());

        $reloaded = $entityManager->find(Event::class, $eventId);
        $this->assertInstanceOf(Event::class, $reloaded);

        $properties    = $reloaded->getProperties();
        $secretService = $this->createSecretService();

        $this->assertTrue($secretService->isEncrypted($properties['password']));
        $this->assertTrue($secretService->isEncrypted($properties['authorization_header']));
        $this->assertSame('legacy-plain-password', $secretService->decryptIfNeeded($properties['password']));
        $this->assertSame('Authorization: Bearer legacy-token', $secretService->decryptIfNeeded($properties['authorization_header']));
    }
}
