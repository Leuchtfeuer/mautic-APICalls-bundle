<?php
namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
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

        // Add the event type to timeline
        $event->addEventType($eventType, $eventTypeName);

        // Check if this event type should be included
        if (!$event->isApplicable($eventType)) {
            return;
        }

        /** @var LeadEventLogRepository $leadEventLogRepo */
        $leadEventLogRepo = $this->entityManager->getRepository(\Mautic\LeadBundle\Entity\LeadEventLog::class);

        // Query for your API call logs
        $options = $event->getQueryOptions();
        $logs = $leadEventLogRepo->getEvents(
            $event->getLead(),
            'LeuchtfeuerAPICallsBundle',
            'api_call',
            'executed',
            $options
        );

        // Add to counter for pagination
        $event->addToCounter($eventType, $logs);

        if (!$event->isEngagementCount()) {
            // Add each log entry to the timeline
            foreach ($logs['results'] as $log) {

                $properties = $log['properties'] ? json_decode($log['properties'], true) : [];
                $event->addEvent([
                    'event'      => $eventType,
                    'eventId'    => $eventType . $log['id'],
                    'eventLabel' => $properties['message'] ?? 'API Request executed',
                    'eventType'  => $eventTypeName,
                    'timestamp'  => $log['date_added'],
                    'icon'       => 'ri-share-box-line',
                    'contactId'  => $log['lead_id'],
                    'extra'      => [
                        'properties' => $properties,
                        'details' => 'Test details',
                        'bundle' => $log['bundle'],
                        'object' => $log['object'],
                        'action' => $log['action']
                    ],
                    'contentTemplate' => '@LeuchtfeuerAPICalls/SubscribedEvents/Timeline/index.html.twig',
                ]);

            }
        }
    }
}