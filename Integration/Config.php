<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

class Config
{
    public function __construct(
        private IntegrationsHelper $integrationsHelper,
    ) {
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->integrationsHelper->getIntegration(ApiCallsIntegration::INTEGRATION_NAME);

            if (!$integration->hasIntegrationConfiguration()) {
                return false;
            }

            return (bool) $integration->getIntegrationConfiguration()->getIsPublished();
        } catch (IntegrationNotFoundException) {
            return false;
        }
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(ApiCallsIntegration::INTEGRATION_NAME);

        if (!$integrationObject->hasIntegrationConfiguration()) {
            throw new IntegrationNotFoundException(ApiCallsIntegration::INTEGRATION_NAME);
        }

        return $integrationObject->getIntegrationConfiguration();
    }
}
