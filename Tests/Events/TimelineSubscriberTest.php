<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\TimelineSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TimelineSubscriberTest extends TestCase
{
    /** @var LeadEventLogRepository&MockObject */
    private MockObject $repository;

    private TimelineSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(LeadEventLogRepository::class);
        $this->subscriber = new TimelineSubscriber($this->repository);
    }

    public function testOnTimelineGenerateUsesMetadataFilterAndSeparateCountQuery(): void
    {
        $lead = $this->createStub(Lead::class);

        $apiLog = $this->createMock(LeadEventLog::class);
        $apiLog->method('getMetadata')->willReturn([
            'event'              => 'api_calls',
            'object_description' => 'API-Request Response',
            'method'             => 'GET',
        ]);
        $apiLog->method('getLead')->willReturn($lead);
        $apiLog->method('getId')->willReturn(42);
        $apiLog->method('getDateTriggered')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $countQuery = $this->createMock(AbstractQuery::class);
        $countQuery->expects($this->once())->method('getSingleScalarResult')->willReturn('2');

        $resultQuery = $this->createMock(AbstractQuery::class);
        $resultQuery->expects($this->once())->method('getResult')->willReturn([$apiLog]);

        $countQueryBuilder = $this->createConfiguredQueryBuilder($countQuery);
        $countQueryBuilder->expects($this->once())->method('select')->with('COUNT(log.id)')->willReturnSelf();
        $countQueryBuilder->expects($this->never())->method('setMaxResults');

        $resultQueryBuilder = $this->createConfiguredQueryBuilder($resultQuery);
        $resultQueryBuilder->expects($this->never())->method('select');
        $resultQueryBuilder->expects($this->once())->method('setMaxResults')->with(1)->willReturnSelf();
        $resultQueryBuilder->expects($this->once())->method('setFirstResult')->with(0)->willReturnSelf();

        $this->repository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->with('log')
            ->willReturnOnConsecutiveCalls($countQueryBuilder, $resultQueryBuilder);

        $event = $this->createTimelineEvent($lead, 1);
        $event->addEventType('leuchtfeuer.api_call', 'API Request');

        $this->subscriber->onTimelineGenerate($event);

        $this->assertSame(2, $event->getEventCounter()['total']);
        $this->assertCount(1, $event->getEvents());
    }

    public function testOnTimelineGenerateReturnsEarlyForEngagementCount(): void
    {
        $lead = $this->createStub(Lead::class);

        $countQuery = $this->createMock(AbstractQuery::class);
        $countQuery->expects($this->once())->method('getSingleScalarResult')->willReturn('3');

        $countQueryBuilder = $this->createConfiguredQueryBuilder($countQuery);
        $countQueryBuilder->expects($this->once())->method('select')->with('COUNT(log.id)')->willReturnSelf();

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('log')
            ->willReturn($countQueryBuilder);

        $event = $this->createTimelineEvent($lead, 1);
        $event->addEventType('leuchtfeuer.api_call', 'API Request');
        $event->setCountOnly(new \DateTime('-1 day'), new \DateTime('+1 day'));

        $this->subscriber->onTimelineGenerate($event);

        $this->assertSame(3, $event->getEventCounter()['total']);
        $this->assertSame([], $event->getEvents());
    }

    private function createTimelineEvent(Lead $lead, int $limit): LeadTimelineEvent
    {
        return new LeadTimelineEvent(
            $lead,
            [
                'search'        => '',
                'includeEvents' => ['leuchtfeuer.api_call'],
                'excludeEvents' => [],
            ],
            null,
            1,
            $limit
        );
    }

    /**
     * @return QueryBuilder&MockObject
     */
    private function createConfiguredQueryBuilder(AbstractQuery $query): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->with('log.metadata LIKE :apiCallsMetadata')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
