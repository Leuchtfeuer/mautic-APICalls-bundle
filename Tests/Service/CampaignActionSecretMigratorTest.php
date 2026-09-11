<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Service;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mautic\CampaignBundle\Entity\Event;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretMigrator;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CampaignActionSecretMigratorTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var CampaignActionSecretService&MockObject */
    private MockObject $secretService;

    private CampaignActionSecretMigrator $migrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->secretService = $this->createMock(CampaignActionSecretService::class);
        $this->migrator      = new CampaignActionSecretMigrator($this->entityManager, $this->secretService);
    }

    public function testContainsPlaintextSecretsDetectsLegacyValues(): void
    {
        $this->assertTrue(CampaignActionSecretMigrator::containsPlaintextSecrets([
            'password' => 'plain-password',
        ]));
        $this->assertTrue(CampaignActionSecretMigrator::containsPlaintextSecrets([
            'authorization_header' => 'Authorization: Bearer token',
        ]));
    }

    public function testContainsPlaintextSecretsIgnoresEncryptedAndEmptyValues(): void
    {
        $this->assertFalse(CampaignActionSecretMigrator::containsPlaintextSecrets([
            'password'             => 'cipher|vector',
            'authorization_header' => '',
        ]));
    }

    public function testMigrateEncryptsPlaintextSecretsAndFlushes(): void
    {
        $event = new Event();
        $event->setProperties([
            'url'                  => 'https://api.example.com',
            'password'             => 'plain-password',
            'authorization_header' => 'Authorization: Bearer token',
        ]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderReturning([$event]));

        $this->secretService->expects($this->exactly(2))
            ->method('encryptIfNeeded')
            ->willReturnCallback(static fn (string $value): string => match ($value) {
                'plain-password'              => 'encrypted-password',
                'Authorization: Bearer token' => 'encrypted-header',
                default                       => $value,
            });

        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame(1, $this->migrator->migrate());
        $this->assertSame('encrypted-password', $event->getProperties()['password']);
        $this->assertSame('encrypted-header', $event->getProperties()['authorization_header']);
    }

    public function testMigrateSkipsAlreadyEncryptedSecrets(): void
    {
        $event = new Event();
        $event->setProperties([
            'password'             => 'cipher|vector',
            'authorization_header' => 'another|vector',
        ]);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->createQueryBuilderReturning([$event]));

        $this->secretService->expects($this->exactly(2))
            ->method('encryptIfNeeded')
            ->willReturnArgument(0);

        $this->entityManager->expects($this->never())->method('flush');

        $this->assertSame(0, $this->migrator->migrate());
    }

    /**
     * @param Event[] $events
     */
    private function createQueryBuilderReturning(array $events): QueryBuilder
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn($events);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
