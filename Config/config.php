<?php

declare(strict_types=1);


use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\ApiCallsIntegration;
use MauticPlugin\LeuchtfeuerAPICallsBundle\Integration\Support\ConfigSupport;

return [
    'name'        => 'API Calls by Leuchtfeuer',
    'description' => 'Allow generic outbound API calls e.g. as campaign action',
    'version'     => '1.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'services'    => [
        'integrations' => [
            'mautic.integration.apicalls' => [
                'class' => ApiCallsIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'mautic.integration.apicalls.configuration' => [
                'class' => ConfigSupport::class,
                'tags'  => [
                    'mautic.config_integration',
                ],
            ],
        ],
    ],
    'routes' => [
        'main' => [
            'apicalls' => [
                'path'       => '/apicalls',
                'controller' => 'MauticPlugin\LeuchtfeuerAPICallsBundle\Controller\ApiCallsController::indexAction'
            ],
        ],
    ],
];
