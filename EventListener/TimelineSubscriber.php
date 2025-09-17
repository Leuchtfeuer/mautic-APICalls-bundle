<?php
namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\TimelineEventLogTrait;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TimelineSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $eventType = 'leuchtfeuer.api_call';
        $eventTypeName = 'API Request';

       $event->addEventType($eventType, $eventTypeName);

        if (!$event->isApplicable($eventType)) {
            return;
        }

        $campaignLeadEventLogRepo = $this->entityManager->getRepository(LeadEventLog::class);
        $options = $event->getQueryOptions();

          // Get campaign event logs with metadata for this lead
        $qb = $campaignLeadEventLogRepo->createQueryBuilder('log')
          ->where('log.lead = :lead')
          ->andWhere('log.metadata IS NOT NULL')
          ->setParameter('lead', $event->getLead())
          ->orderBy('log.dateTriggered', 'DESC');

         // Apply pagination if needed
         if (isset($options['limit'])) {
             $qb->setMaxResults($options['limit']);
         }

        $logs = $qb->getQuery()->getResult();

        // Add to counter
        $event->addToCounter($eventType, count($logs));

        if (!$event->isEngagementCount()) {

            /** @var LeadEventLog $log */
            foreach ($logs as $log) {
                $metadata = $log->getMetadata();
                if (isset($metadata['event']) && $metadata['event'] === 'api_calls' && isset($metadata['method'])) {
                    $event->addEvent([
                        'event'      => $eventType . $metadata['method'],
                        'eventId'    => $eventType . $log->getId(),
                        'eventLabel' => $metadata['object_description'] . '/' . $metadata['method'],
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log->getDateTriggered(),
                        'icon'       => 'ri-share-box-line',
                        'contactId'  => $log->getLead()->getId(),
                        'extra'      => [
                            'properties' => $metadata,
                            'details' => 'Test details',
                            'bundle' => $metadata['bundle'] ?? null,
                            'object' => $metadata['object'] ?? null,
                            'action' => $metadata['action'] ?? null
                        ],
                        'contentTemplate' => '@LeuchtfeuerAPICalls/SubscribedEvents/Timeline/index.html.twig',
                    ]);
                }
            }
        }
    }
}