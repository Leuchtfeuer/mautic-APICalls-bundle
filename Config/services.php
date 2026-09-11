<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes= [];

    $services->load('MauticPlugin\\LeuchtfeuerAPICallsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->get(ApiCallsIntegration::class)
        ->tag('mautic.integration')
        ->tag('mautic.basic_integration');

    $services->get(ConfigSupport::class)
        ->tag('mautic.config_integration');

    $services->alias('mautic.integration.apicalls', ApiCallsIntegration::class);
    $services->alias('mautic.integration.apicalls.configuration', ConfigSupport::class);
};
