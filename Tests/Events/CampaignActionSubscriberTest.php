<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Events;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener\CampaignActionSubscriber;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\ContactProcessorService;
use PHPUnit\Framework\MockObject\MockObject;

  class CampaignActionSubscriberTest extends MauticMysqlTestCase
  {
      private ContactProcessorService|MockObject $contactProcessorService;
      private IntegrationsHelper|MockObject $integrationsHelper;
      private CampaignActionSubscriber $subscriber;

      protected function setUp(): void
      {
          parent::setUp();

          $this->contactProcessorService = $this->createMock(ContactProcessorService::class);
          $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
          $this->subscriber = new CampaignActionSubscriber($this->contactProcessorService, $this->integrationsHelper);
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

          $event = $this->createMock(PendingEvent::class);
          $event->expects($this->once())
              ->method('getContacts')
              ->willReturn($contacts);
          $event->expects($this->once())
              ->method('getEvent')
              ->willReturn($campaignEvent);
          $event->expects($this->once())
              ->method('passAll');

          $this->contactProcessorService->expects($this->once())
              ->method('processContacts')
              ->with($contacts, $properties);

          $this->subscriber->onExecuteApiRequest($event);
      }

      public function testOnExecuteApiRequestFailsAllWhenExceptionOccurs(): void
      {
          $contacts = [new Lead()];
          $properties = ['url' => 'https://api.example.com', 'method' => 'POST'];
          $errorMessage = 'API request failed';

          $campaignEvent = $this->createMock(\Mautic\CampaignBundle\Entity\Event::class);
          $campaignEvent->expects($this->once())
              ->method('getProperties')
              ->willReturn($properties);

          $event = $this->createMock(PendingEvent::class);
          $event->expects($this->once())
              ->method('getContacts')
              ->willReturn($contacts);
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
              ->with($contacts, $properties)
              ->willThrowException(new \Exception($errorMessage));

          $this->subscriber->onExecuteApiRequest($event);
      }
  }
