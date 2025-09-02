<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'mautic.leuchtfeuer.api_request';

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION => ['onExecuteApiRequest', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            self::ACTION_TYPE,
            [
                'label'          => 'API Request Action',
                'description'    => 'Send API request with tokens',
                'batchEventName' => LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION,
                'formType'       => ApiRequestActionType::class,
            ]
        );
    }



    public function onExecuteApiRequest(PendingEvent $event): void
    {
        $test = $event->getContacts();
            try {

                $event->pass($event);

            } catch (\Throwable $e) {

                $event->fail();

            }

    }

}