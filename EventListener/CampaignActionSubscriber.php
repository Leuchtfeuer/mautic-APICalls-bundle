<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'mautic.leuchtfeuer.api_request';

    public function __construct(private ContactProcessorService $contactProcessorService,  private IntegrationsHelper $integrationsHelper){}
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION => ['onExecuteApiRequest', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $integrationConfiguration = $this->integrationsHelper->getIntegration(ApiCallsIntegration::INTEGRATION_NAME)->getIntegrationConfiguration();

            if(!$integrationConfiguration->getIsPublished()) {
                return;
            }

            $event->addAction(
                self::ACTION_TYPE,
                [
                    'label' => 'leuchtfeuer.api.action.label',
                    'description' => 'leuchtfeuer.api.action.description',
                    'batchEventName' => LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION,
                    'formType' => ApiRequestActionType::class,
                ]
            );

    }

    public function onExecuteApiRequest(PendingEvent $event): void
    {
        try {
            $this->contactProcessorService->processContacts($event->getEvent()->getProperties(), $event->getPending()->toArray());
            $event->passAll();
        } catch (\Throwable $e) {
            $event->failAll($e->getMessage());
        }
    }


}