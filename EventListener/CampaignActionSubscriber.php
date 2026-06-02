<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Config;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignActionSubscriber implements EventSubscriberInterface
{
    public const ACTION_TYPE = 'mautic.leuchtfeuer.api_request';

    public const UNPUBLISHED_FAILURE_REASON = 'leuchtfeuer.mautic-apicalls-bundle.action.unpublished';

    /**
     * @var array<string, string>
     */
    public const FORM_TYPE_CLEAN_MASKS = [
        'body'                 => 'raw',
        'url_parameters'       => 'raw',
        'authorization_header' => 'string',
        'regex'                => 'string',
    ];

    public function __construct(
        private ContactProcessorService $contactProcessorService,
        private Config $config,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                  => ['onCampaignBuild', 0],
            LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION => ['onExecuteApiRequest', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $event->addAction(
            self::ACTION_TYPE,
            [
                'label'              => 'leuchtfeuer.mautic-apicalls-bundle.action.label',
                'description'        => 'leuchtfeuer.mautic-apicalls-bundle.action.description',
                'batchEventName'     => LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION,
                'formType'           => ApiRequestActionType::class,
                'formTypeCleanMasks' => self::FORM_TYPE_CLEAN_MASKS,
            ]
        );
    }

    public function onExecuteApiRequest(PendingEvent $event): void
    {
        if (!$this->config->isPublished()) {
            $event->failAll($this->translator->trans(self::UNPUBLISHED_FAILURE_REASON));

            return;
        }

        try {
            /** @var LeadEventLog[] $leads */
            $leads = $event->getPending()->toArray();

            $this->contactProcessorService->processContacts($event->getEvent()->getProperties(), $leads);
            $event->passAll();
        } catch (\Throwable $e) {
            $event->failAll($e->getMessage());
        }
    }
}
