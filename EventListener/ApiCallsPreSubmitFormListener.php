<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ApiCallsPreSubmitFormListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmitData',
        ];
    }

    public function preSubmitData(FormEvent $event): void
    {
        $data = $event->getData();
        $postCampaignEvent = $_POST['campaignevent'];

        if($postCampaignEvent['type'] == 'mautic.leuchtfeuer.api_request') {

            $data['body'] = $postCampaignEvent['properties']['body'];

            $event->setData($data);
        }

    }
}

