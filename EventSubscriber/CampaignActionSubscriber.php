<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventSubscriber;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerApiCallsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'mautic.leuchtfeuer_apicalls.api_request_executor.on_execute_api_request';

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD            => ['onCampaignBuild', 0],
            LeuchtfeuerApiCallsEvent::API_REQUEST_EVENT  => ['onExecuteCampaignAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $test = $event;
        $event->addAction(
            self::ACTION_TYPE,
            [
                'label'          => 'API Request Action',
                'description'    => 'Send API request with tokens',
                'batchEventName' => LeuchtfeuerApiCallsEvent::API_REQUEST_EVENT,
                'formType'       => ApiRequestActionType::class,
            ]
        );
    }


    public function onExecuteCampaignAction(PendingEvent $pendingEvent): void
    {
       $test = $pendingEvent;
        $pendingEvent->passRemaining();
    }
}