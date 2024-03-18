<?php

return [
    'frontend' => [
        'georgringer/oidc-be-fe-redirect' => [
            'target' => \GeorgRinger\OidcBe\Middleware\BackendRedirectInFeHandler::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
    'backend' => [
        'georgringer/oidc-be' => [
            'target' => \GeorgRinger\OidcBe\Middleware\OidcRequestHandler::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-backend/site-resolver',
            ],
        ],
    ],
];