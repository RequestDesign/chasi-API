<?php
return [
    'controllers' => [
        'value' => [
            'namespace' => '\\Site\\Api\\',
            'restIntegration' => [
                'enabled' => true,
            ],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            'site.api.ad' => [
                'className' => \Site\Api\Services\AdService::class
            ]
        ],
        'readonly' => true
    ]
];
