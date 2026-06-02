<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Tests\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /** @var IntegrationsHelper&MockObject */
    private IntegrationsHelper $integrationsHelper;

    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->config             = new Config($this->integrationsHelper);
    }

    public function testIsPublishedReturnsTrueWhenIntegrationIsConfiguredAndPublished(): void
    {
        $integration = $this->createMock(ApiCallsIntegration::class);
        $entity      = $this->createMock(Integration::class);

        $entity->method('getIsPublished')->willReturn(true);
        $integration->method('hasIntegrationConfiguration')->willReturn(true);
        $integration->method('getIntegrationConfiguration')->willReturn($entity);

        $this->integrationsHelper
            ->method('getIntegration')
            ->with(ApiCallsIntegration::INTEGRATION_NAME)
            ->willReturn($integration);

        self::assertTrue($this->config->isPublished());
    }

    public function testIsPublishedReturnsFalseWhenIntegrationIsNotFound(): void
    {
        $this->integrationsHelper
            ->method('getIntegration')
            ->willThrowException(new IntegrationNotFoundException(ApiCallsIntegration::INTEGRATION_NAME));

        self::assertFalse($this->config->isPublished());
    }

    public function testIsPublishedReturnsFalseWhenIntegrationConfigurationIsMissing(): void
    {
        $integration = $this->createMock(ApiCallsIntegration::class);
        $integration->method('hasIntegrationConfiguration')->willReturn(false);

        $this->integrationsHelper
            ->method('getIntegration')
            ->willReturn($integration);

        self::assertFalse($this->config->isPublished());
    }

    public function testIsPublishedReturnsFalseWhenIntegrationIsUnpublished(): void
    {
        $integration = $this->createMock(ApiCallsIntegration::class);
        $entity      = $this->createMock(Integration::class);

        $entity->method('getIsPublished')->willReturn(false);
        $integration->method('hasIntegrationConfiguration')->willReturn(true);
        $integration->method('getIntegrationConfiguration')->willReturn($entity);

        $this->integrationsHelper
            ->method('getIntegration')
            ->willReturn($integration);

        self::assertFalse($this->config->isPublished());
    }
}
