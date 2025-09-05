<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\LeuchtfeuerAPICallsEvents;


class CampaignActionSubscriberTest extends TestCase
{
    private CampaignActionSubscriber $subscriber;
    private ContactProcessorService|MockObject $contactProcessorService;
    private IntegrationsHelper|MockObject $integrationsHelper;

    protected function setUp(): void
    {
        $this->contactProcessorService = $this->createMock(ContactProcessorService::class);
        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);

        $this->subscriber = new CampaignActionSubscriber(
            $this->contactProcessorService,
            $this->integrationsHelper
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $expectedEvents = [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION => ['onExecuteApiRequest', 0],
        ];

        $this->assertEquals($expectedEvents, CampaignActionSubscriber::getSubscribedEvents());
    }

    public function testOnCampaignBuildWithPublishedIntegration(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $integration = $this->createMock(IntegrationInterface::class);
        $integrationConfiguration = $this->createMock(Integration::class);

        $integrationConfiguration->expects($this->once())
            ->method('getIsPublished')
            ->willReturn(true);

        $integration->expects($this->once())
            ->method('getIntegrationConfiguration')
            ->willReturn($integrationConfiguration);

        $this->integrationsHelper->expects($this->once())
            ->method('getIntegration')
            ->with(ApiCallsIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $event->expects($this->once())
            ->method('addAction')
            ->with(
                CampaignActionSubscriber::ACTION_TYPE,
                [
                    'label' => 'leuchtfeuer.api.action.label',
                    'description' => 'leuchtfeuer.api.action.description',
                    'batchEventName' => LeuchtfeuerAPICallsEvents::EXECUTE_CAMPAIGN_ACTION,
                    'formType' => ApiRequestActionType::class,
                ]
            );

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnCampaignBuildWithUnpublishedIntegration(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $integration = $this->createMock(IntegrationInterface::class);
        $integrationConfiguration = $this->createMock(Integration::class);

        $integrationConfiguration->expects($this->once())
            ->method('getIsPublished')
            ->willReturn(false);

        $integration->expects($this->once())
            ->method('getIntegrationConfiguration')
            ->willReturn($integrationConfiguration);

        $this->integrationsHelper->expects($this->once())
            ->method('getIntegration')
            ->with(ApiCallsIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $event->expects($this->never())
            ->method('addAction');

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnExecuteApiRequestSuccess(): void
    {
        $event = $this->createMock(PendingEvent::class);

        $campaignEvent = new class {
            public function getProperties(): array
            {
                return ['key' => 'value'];
            }
        };

        $contacts = ['contact1', 'contact2'];
        $properties = ['key' => 'value'];

        $event->expects($this->once())
            ->method('getContacts')
            ->willReturn($contacts);

        $event->expects($this->once())
            ->method('getEvent')
            ->willReturn($campaignEvent);

        $this->contactProcessorService->expects($this->once())
            ->method('processContacts')
            ->with($contacts, $properties);

        $event->expects($this->once())
            ->method('passAll');

        $event->expects($this->never())
            ->method('failAll');

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnExecuteApiRequestFailure(): void
    {
        $event = $this->createMock(PendingEvent::class);

        $campaignEvent = new class {
            public function getProperties(): array
            {
                return ['key' => 'value'];
            }
        };

        $contacts = ['contact1', 'contact2'];
        $properties = ['key' => 'value'];
        $exceptionMessage = 'Processing failed';

        $event->expects($this->once())
            ->method('getContacts')
            ->willReturn($contacts);

        $event->expects($this->once())
            ->method('getEvent')
            ->willReturn($campaignEvent);

        $this->contactProcessorService->expects($this->once())
            ->method('processContacts')
            ->with($contacts, $properties)
            ->willThrowException(new \Exception($exceptionMessage));

        $event->expects($this->never())
            ->method('passAll');

        $event->expects($this->once())
            ->method('failAll')
            ->with($exceptionMessage);

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testActionTypeConstant(): void
    {
        $this->assertEquals('mautic.leuchtfeuer.api_request', CampaignActionSubscriber::ACTION_TYPE);
    }
}
