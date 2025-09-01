<?php

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
    }
    public static function getSubscribedEvents()
    {
        return [];
    }

    public function onBuildMenu(): void
    {
    }
}
