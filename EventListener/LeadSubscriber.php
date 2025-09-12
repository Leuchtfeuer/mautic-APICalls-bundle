<?php
namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;

use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


final class LeadSubscriber implements EventSubscriberInterface
{


    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}


    public static function getSubscribedEvents(): array
    {
        return [
            LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION => ['onCampaignActionExecute', 0],
        ];
    }

    public function onCampaignActionExecute(PendingEvent $event): void
    {
        $logs = $event->getPending();

        foreach ($logs as $log) {

            $log->setMetadata([
                'event' => 'api_calls',
                'object_description' => 'Peter test'
            ]);

        }
        $event->passLogs($logs);
    }


}