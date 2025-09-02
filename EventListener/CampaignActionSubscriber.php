<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Helper\CustomFieldHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ApiCallsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'mautic.leuchtfeuer.api_request';


    public function __construct(private ApiCallsService $apiCallsService){}
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
        $contacts = $event->getContacts();
        $properties   = $event->getEvent()->getProperties();

        foreach ($contacts as $contact) {

                $tokenizedValue = TokenHelper::findLeadTokens($properties['body'], $contact->getProfileFields(), true);
                $this->apiCallsService->sendRequest($tokenizedValue, $properties['method'], $properties['url']);
        }

            try {
                $event->pass();
            } catch (\Throwable $e) {
                $event->fail();
            }
        }




}