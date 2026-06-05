<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Migrations\Version_6_1_0;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretMigrator;

class LeuchtfeuerAPICallsBundle extends AbstractPluginBundle
{
    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, mixed $metadata = null, mixed $installedSchema = null): void
    {
        self::onPluginUpdate($plugin, $factory, is_array($metadata) ? $metadata : null, $installedSchema);
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public static function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, ?Schema $installedSchema = null): void
    {
        /** @var CampaignActionSecretMigrator $migrator */
        $migrator = $factory->get(CampaignActionSecretMigrator::class);
        Version_6_1_0::setMigrator($migrator);

        parent::onPluginUpdate($plugin, $factory, $metadata, $installedSchema);
    }
}
