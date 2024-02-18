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
            ],
            'site.api.adv' => [
                'className' => \Site\Api\Services\AdvService::class
            ],
            'site.api.user' => [
                'className' => \Site\Api\Services\UserService::class
            ]
        ],
        'readonly' => true
    ]
];
