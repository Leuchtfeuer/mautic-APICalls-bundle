<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\EventListener;

use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Migrations\Version_6_1_0;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretMigrator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginUpdateSubscriber implements EventSubscriberInterface
{
    private const PLUGIN_NAME = 'API Calls by Leuchtfeuer';

    public function __construct(
        private readonly CampaignActionSecretMigrator $migrator,
    ) {
    }

    public function onUpdate(PluginUpdateEvent $event): void
    {
        if (!$event->checkContext(self::PLUGIN_NAME)) {
            return;
        }

        Version_6_1_0::setMigrator($this->migrator);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Priority higher than PluginSubscriber::onUpdate() (0), so the migrator
            // is set before Engine::up() runs the migration.
            PluginEvents::ON_PLUGIN_UPDATE => ['onUpdate', 10],
        ];
    }
}
