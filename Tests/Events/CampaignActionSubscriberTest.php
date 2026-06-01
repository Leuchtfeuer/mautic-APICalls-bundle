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
    private ContactProcessorService $contactProcessorService;

    /** @var Config&MockObject */
    private Config $config;

    /** @var TranslatorInterface&MockObject */
    private TranslatorInterface $translator;

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

        self::assertArrayHasKey('mautic.campaign_on_build', $events);
        self::assertArrayHasKey('api.campaign_action.execute', $events);
        self::assertSame(['onCampaignBuild', 0], $events['mautic.campaign_on_build']);
        self::assertSame(['onExecuteApiRequest', 0], $events['api.campaign_action.execute']);
    }

    public function testOnExecuteApiRequestProcessesContactsSuccessfully(): void
    {
        $contacts   = [new Lead()];
        $properties = ['url' => 'https://api.example.com', 'method' => 'POST'];

        $this->config->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);

        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->expects(self::once())
            ->method('getProperties')
            ->willReturn($properties);

        $pending = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $pending->expects(self::once())
            ->method('toArray')
            ->willReturn($contacts);

        $event = $this->createMock(PendingEvent::class);
        $event->expects(self::once())
            ->method('getPending')
            ->willReturn($pending);
        $event->expects(self::once())
            ->method('getEvent')
            ->willReturn($campaignEvent);
        $event->expects(self::once())
            ->method('passAll');

        $this->contactProcessorService->expects(self::once())
            ->method('processContacts')
            ->with($properties, $contacts);

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnExecuteApiRequestFailsAllWhenIntegrationIsNotPublished(): void
    {
        $failureMessage = 'The API Calls plugin is not published.';

        $this->config->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(CampaignActionSubscriber::UNPUBLISHED_FAILURE_REASON)
            ->willReturn($failureMessage);

        $event = $this->createMock(PendingEvent::class);
        $event->expects(self::once())
            ->method('failAll')
            ->with($failureMessage);
        $event->expects(self::never())
            ->method('passAll');
        $event->expects(self::never())
            ->method('getPending');

        $this->contactProcessorService->expects(self::never())
            ->method('processContacts');

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnExecuteApiRequestFailsAllWhenExceptionOccurs(): void
    {
        $contacts     = [new Lead()];
        $properties   = ['url' => 'https://api.example.com', 'method' => 'POST'];
        $errorMessage = 'API request failed';

        $this->config->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);

        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->expects(self::once())
            ->method('getProperties')
            ->willReturn($properties);

        $pending = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $pending->expects(self::once())
            ->method('toArray')
            ->willReturn($contacts);

        $event = $this->createMock(PendingEvent::class);
        $event->expects(self::once())
            ->method('getPending')
            ->willReturn($pending);
        $event->expects(self::once())
            ->method('getEvent')
            ->willReturn($campaignEvent);
        $event->expects(self::once())
            ->method('failAll')
            ->with($errorMessage);
        $event->expects(self::never())
            ->method('passAll');

        $this->contactProcessorService->expects(self::once())
            ->method('processContacts')
            ->with($properties, $contacts)
            ->willThrowException(new \Exception($errorMessage));

        $this->subscriber->onExecuteApiRequest($event);
    }

    public function testOnCampaignBuildAddsActionWhenIntegrationIsPublished(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);

        $this->config->expects(self::once())
            ->method('isPublished')
            ->willReturn(true);

        $event->expects(self::once())
            ->method('addAction')
            ->with(
                CampaignActionSubscriber::ACTION_TYPE,
                [
                    'label'              => 'leuchtfeuer.mautic-apicalls-bundle.action.label',
                    'description'        => 'leuchtfeuer.mautic-apicalls-bundle.action.description',
                    'batchEventName'     => 'api.campaign_action.execute',
                    'formType'           => ApiRequestActionType::class,
                    'formTypeCleanMasks' => CampaignActionSubscriber::FORM_TYPE_CLEAN_MASKS,
                ]
            );

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnCampaignBuildDoesNotAddActionWhenIntegrationIsNotPublished(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);

        $this->config->expects(self::once())
            ->method('isPublished')
            ->willReturn(false);

        $event->expects(self::never())
            ->method('addAction');

        $this->subscriber->onCampaignBuild($event);
    }
}
