<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Services\CampaignActionSecretMigrator;

class Version_5_1_0 extends AbstractMigration
{
    private static ?CampaignActionSecretMigrator $migrator = null;

    public static function setMigrator(CampaignActionSecretMigrator $migrator): void
    {
        self::$migrator = $migrator;
    }

    protected function isApplicable(Schema $schema): bool
    {
        try {
            if (!$schema->hasTable($this->concatPrefix('campaign_events'))) {
                return false;
            }
        } catch (SchemaException) {
            return false;
        }

        $migrator = $this->getMigrator();

        return $migrator->hasPendingSecrets();
    }

    protected function up(): void
    {
        $this->getMigrator()->migrate();
    }

    private function getMigrator(): CampaignActionSecretMigrator
    {
        if (null === self::$migrator) {
            throw new \RuntimeException('CampaignActionSecretMigrator must be set before running Version_5_1_0 migration.');
        }

        return self::$migrator;
    }
}
