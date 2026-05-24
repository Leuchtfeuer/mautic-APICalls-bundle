<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use PHPUnit\Framework\MockObject\MockObject;

class CampaignActionSubscriberTest extends MauticMysqlTestCase
{
    private MockObject $contactProcessorService;
    private MockObject $integrationsHelper;
    private CampaignActionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactProcessorService = $this->createMock(ContactProcessorService::class);
        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->subscriber = new CampaignActionSubscriber(
            $this->contactProcessorService,
            $this->integrationsHelper
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CampaignActionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('mautic.campaign_on_build', $events);
        $this->assertArrayHasKey('api.campaign_action.execute', $events);
        $this->assertEquals(['onCampaignBuild', 0], $events['mautic.campaign_on_build']);
        $this->assertEquals(['onExecuteApiRequest', 0], $events['api.campaign_action.execute']);
    }


    public function testOnExecuteApiRequestProcessesContactsSuccessfully(): void
    {
        $contacts = [new Lead()];
        $properties = ['url' => 'https://api.example.com', 'method' => 'POST'];

        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $pending = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $pending->expects($this->once())
            ->method('toArray')
            ->willReturn($contacts);

        $event = $this->createMock(PendingEvent::class);
        $event->expects($this->once())
            ->method('getPending')
            ->willReturn($pending);
        $event->expects($this->once())
            ->method('getEvent')
            ->willReturn($campaignEvent);
        $event->expects($this->once())
            ->method('passAll');

        $this->contactProcessorService->expects($this->once())
            ->method('processContacts')
            ->with($properties, $contacts);

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnExecuteApiRequestFailsAllWhenExceptionOccurs(): void
    {
        $contacts = [new Lead()];
        $properties = ['url' => 'https://api.example.com', 'method' => 'POST'];
        $errorMessage = 'API request failed';

        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->expects($this->once())
            ->method('getProperties')
            ->willReturn($properties);

        $pending = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $pending->expects($this->once())
            ->method('toArray')
            ->willReturn($contacts);

        $event = $this->createMock(PendingEvent::class);
        $event->expects($this->once())
            ->method('getPending')
            ->willReturn($pending);
        $event->expects($this->once())
            ->method('getEvent')
            ->willReturn($campaignEvent);
        $event->expects($this->once())
            ->method('failAll')
            ->with($errorMessage);
        $event->expects($this->never())
            ->method('passAll');

        $this->contactProcessorService->expects($this->once())
            ->method('processContacts')
            ->with($properties, $contacts)
            ->willThrowException(new \Exception($errorMessage));

        $this->subscriber->onExecuteApiRequest($event);
    }


    public function testOnCampaignBuildAddsActionWhenIntegrationIsPublished(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $integration = $this->createMock(ApiCallsIntegration::class);
        $integrationConfiguration = $this->createMock(\Mautic\PluginBundle\Entity\Integration::class);

        $integrationConfiguration->expects($this->once())
            ->method('getIsPublished')
            ->willReturn(true);

        $integration->expects($this->once())
            ->method('hasIntegrationConfiguration')
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
                    'label' => 'leuchtfeuer.mautic-apicalls-bundle.action.label',
                    'description' => 'leuchtfeuer.mautic-apicalls-bundle.action.description',
                    'batchEventName' => 'api.campaign_action.execute',
                    'formType' => ApiRequestActionType::class,
                    'formTypeCleanMasks' => CampaignActionSubscriber::FORM_TYPE_CLEAN_MASKS,
                ]
            );

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnCampaignBuildDoesNotAddActionWhenIntegrationIsNotPublished(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $integration = $this->createMock(ApiCallsIntegration::class);
        $integrationConfiguration = $this->createMock(\Mautic\PluginBundle\Entity\Integration::class);

        $integrationConfiguration->expects($this->once())
            ->method('getIsPublished')
            ->willReturn(false);

        $integration->expects($this->once())
            ->method('hasIntegrationConfiguration')
            ->willReturn(true);

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

    public function testOnCampaignBuildDoesNotAddActionWhenIntegrationIsNotFound(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);

        $this->integrationsHelper->expects($this->once())
            ->method('getIntegration')
            ->with(ApiCallsIntegration::INTEGRATION_NAME)
            ->willThrowException(new IntegrationNotFoundException('apicalls'));

        $event->expects($this->never())
            ->method('addAction');

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnCampaignBuildDoesNotAddActionWhenIntegrationConfigurationIsMissing(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);
        $integration = $this->createMock(ApiCallsIntegration::class);

        $integration->expects($this->once())
            ->method('hasIntegrationConfiguration')
            ->willReturn(false);

        $integration->expects($this->never())
            ->method('getIntegrationConfiguration');

        $this->integrationsHelper->expects($this->once())
            ->method('getIntegration')
            ->with(ApiCallsIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        $event->expects($this->never())
            ->method('addAction');

        $this->subscriber->onCampaignBuild($event);
    }
}
