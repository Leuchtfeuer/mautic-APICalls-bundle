<?php
namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Event\PendingEvent;

use Mautic\LeadBundle\Entity\LeadEventLog;
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
        $contacts = $event->getContacts();

        foreach ($contacts as $contact) {

            $logEntry = new LeadEventLog();
            $logEntry->setLead($contact);
            $logEntry->setUserId(null);
            $logEntry->setUserName(null);
            $logEntry->setBundle('LeuchtfeuerAPICallsBundle');
            $logEntry->setObject('api_call');
            $logEntry->setObjectId($event->getEvent()->getId()); // Campaign event ID
            $logEntry->setAction('executed');
            $logEntry->setProperties([
                'message' => 'Hallo world',
                'campaign_id' => $event->getEvent()->getCampaign()->getId(),
                'campaign_name' => $event->getEvent()->getCampaign()->getName(),
                'object_description' => 'Test details',
                'event_name' => $event->getEvent()->getName(),
            ]);
            $logEntry->setDateAdded(new \DateTime());


            $this->entityManager->persist($logEntry);
        }

        $this->entityManager->flush();

        $event->passAll();
    }


}