<?php

declare(strict_types=1);

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
        if (!is_array($data)) {
            return;
        }

        if (!empty($data['password'])) {
            return;
        }

        $originalData = $event->getForm()->getRoot()->getData();
        if (!is_array($originalData) || !isset($originalData['properties']['password'])) {
            return;
        }

        $data['password'] = $originalData['properties']['password'];
        $event->setData($data);
    }
}
