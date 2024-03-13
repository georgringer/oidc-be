<?php

return [
    'backend' => [
        'georgringer/oidc-be' => [
            'target' => \GeorgRinger\OidcBe\Middleware\OidcRequestHandler::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute'
            ],
            'before' => [
                'typo3/cms-backend/site-resolver'
            ]
        ]
    ]
];