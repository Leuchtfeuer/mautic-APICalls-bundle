<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventSubscriber;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Event\ApiRequestExecutor;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'apicalls.api_request_action';

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            self::ACTION_TYPE,
            [
                'label'          => 'API Request Action',
                'description'    => 'Send API request with tokens',
                'batchEventName' => ApiRequestExecutor::class,
                'formType'       => ApiRequestActionType::class,
            ]
        );
    }
}
