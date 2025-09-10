<?php
namespace MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;

class ConfigSupport extends ApiCallsIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}