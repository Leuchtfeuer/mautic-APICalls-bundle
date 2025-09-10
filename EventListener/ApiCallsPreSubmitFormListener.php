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
        $data = is_array($data) ? $data : [];
        $postCampaignEvent = $_POST['campaignevent'] ?? [];

        if (($postCampaignEvent['type'] ?? '') === 'mautic.leuchtfeuer.api_request') {
            $properties = $postCampaignEvent['properties'] ?? [];

            if (empty($properties['password'])) {
                $originalData = $event->getForm()->getRoot()->getData();
                if (isset($originalData['properties']['password'])) {
                    $properties['password'] = $originalData['properties']['password'];
                }
            }

            $data = array_merge($data, $properties);
            $event->setData($data);
        }
    }

}

