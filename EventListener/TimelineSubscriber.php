<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class TimelineSubscriber implements EventSubscriberInterface
{
    private const EVENT_TYPE = 'leuchtfeuer.api_call';

    /**
     * Mautic stores metadata as PHP-serialized arrays in the database.
     */
    private const METADATA_FILTER = '%s:5:"event";s:9:"api_calls"%';

    public function __construct(
        private LeadEventLogRepository $leadEventLogRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $eventType     = self::EVENT_TYPE;
        $eventTypeName = 'API Request';

        $event->addEventType($eventType, $eventTypeName);

        if (!$event->isApplicable($eventType)) {
            return;
        }

        $lead = $event->getLead();

        if (!$lead instanceof Lead) {
            return;
        }

        $options = $event->getQueryOptions();

        $total = (int) $this->createApiCallLogsQueryBuilder($lead)
            ->select('COUNT(log.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $event->addToCounter($eventType, $total);

        if ($event->isEngagementCount()) {
            return;
        }

        $queryBuilder = $this->createApiCallLogsQueryBuilder($lead);

        if (isset($options['limit'])) {
            $queryBuilder->setMaxResults($options['limit']);
        }

        if (isset($options['start'])) {
            $queryBuilder->setFirstResult($options['start']);
        }

        /** @var LeadEventLog[] $logs */
        $logs = $queryBuilder->getQuery()->getResult();

        foreach ($logs as $log) {
            $metadata = $log->getMetadata();

            if (!isset($metadata['method'])) {
                continue;
            }

            $logLead = $log->getLead();

            if (null === $logLead) {
                continue;
            }

            $event->addEvent([
                'event'      => $eventType,
                'eventId'    => $eventType.$log->getId(),
                'eventLabel' => $metadata['object_description'].'/'.$metadata['method'],
                'eventType'  => $eventTypeName,
                'timestamp'  => $log->getDateTriggered(),
                'icon'       => 'ri-share-box-line',
                'contactId'  => $logLead->getId(),
                'extra'      => [
                    'properties' => $metadata,
                    'bundle'     => $metadata['bundle'] ?? null,
                    'object'     => $metadata['object'] ?? null,
                    'action'     => $metadata['action'] ?? null,
                ],
                'contentTemplate' => '@LeuchtfeuerAPICalls/SubscribedEvents/Timeline/index.html.twig',
            ]);
        }
    }

    private function createApiCallLogsQueryBuilder(Lead $lead): QueryBuilder
    {
        return $this->leadEventLogRepository->createQueryBuilder('log')
            ->where('log.lead = :lead')
            ->andWhere('log.metadata LIKE :apiCallsMetadata')
            ->setParameter('lead', $lead)
            ->setParameter('apiCallsMetadata', self::METADATA_FILTER)
            ->orderBy('log.dateTriggered', 'DESC');
    }
}
