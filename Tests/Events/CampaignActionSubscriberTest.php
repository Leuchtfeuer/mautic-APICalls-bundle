<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Form\Type\ApiRequestActionType;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Config;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignActionSubscriberTest extends TestCase
{
    /** @var ContactProcessorService&MockObject */
    private MockObject $contactProcessorService;

    /** @var Config&MockObject */
    private MockObject $config;

    /** @var TranslatorInterface&MockObject */
    private MockObject $translator;

    private CampaignActionSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactProcessorService = $this->createMock(ContactProcessorService::class);
        $this->config                  = $this->createMock(Config::class);
        $this->translator              = $this->createMock(TranslatorInterface::class);
        $this->subscriber              = new CampaignActionSubscriber(
            $this->contactProcessorService,
            $this->config,
            $this->translator,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CampaignActionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('mautic.campaign_on_build', $events);
        $this->assertArrayHasKey('api.campaign_action.execute', $events);
        $this->assertSame(['onCampaignBuild', 0], $events['mautic.campaign_on_build']);
        $this->assertSame(['onExecuteApiRequest', 0], $events['api.campaign_action.execute']);
    }

    public function testOnExecuteApiRequestProcessesContactsSuccessfully(): void
    {
        $contacts   = [new Lead()];
        $properties = ['url' => 'https://api.example.com', 'method' => 'POST'];

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

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

    public function testOnExecuteApiRequestFailsAllWhenIntegrationIsNotPublished(): void
    {
        $failureMessage = 'The API Calls plugin is not published.';

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CampaignActionSubscriber::UNPUBLISHED_FAILURE_REASON)
            ->willReturn($failureMessage);

        $event = $this->createMock(PendingEvent::class);
        $event->expects($this->once())
            ->method('failAll')
            ->with($failureMessage);
        $event->expects($this->never())
            ->method('passAll');
        $event->expects($this->never())
            ->method('getPending');

        $this->contactProcessorService->expects($this->never())
            ->method('processContacts');

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnExecuteApiRequestFailsAllWhenExceptionOccurs(): void
    {
        $contacts     = [new Lead()];
        $properties   = ['url' => 'https://api.example.com', 'method' => 'POST'];
        $errorMessage = 'API request failed';

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

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
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $event = new CampaignBuilderEvent($translator);

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->subscriber->onCampaignBuild($event);

        $this->assertSame([
            'label'              => 'leuchtfeuer.mautic-apicalls-bundle.action.label',
            'description'        => 'leuchtfeuer.mautic-apicalls-bundle.action.description',
            'batchEventName'     => 'api.campaign_action.execute',
            'formType'           => ApiRequestActionType::class,
            'formTypeCleanMasks' => CampaignActionSubscriber::FORM_TYPE_CLEAN_MASKS,
        ], $event->getActions()[CampaignActionSubscriber::ACTION_TYPE]);
    }

    public function testOnCampaignBuildDoesNotAddActionWhenIntegrationIsNotPublished(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $event      = new CampaignBuilderEvent($translator);

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $this->subscriber->onCampaignBuild($event);

        $this->assertSame([], $event->getActions());
    }
}
