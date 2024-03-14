<?php

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1710277788] = [
    'provider' => \GeorgRinger\OidcBe\Backend\OidcLoginProvider::class,
    'sorting' => 50,
    'iconIdentifier' => 'ext-oidcbe-icon',
    'label' => 'Login with OpenID Connect',
];

$settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('oidc') ?? [];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'oidc_be',
    'auth',
    \GeorgRinger\OidcBe\Service\OidcBeAuthService::class,
    [
        'title' => 'Authentication service',
        'description' => 'Authentication service for OpenID Connect BE',
        'subtype' => 'getUserBE,authUserBE,getGroupsBE',
        'available' => true,
        'priority' => 30,
        'quality' => (int)($settings['authenticationServiceQuality'] ?? 80),
        'os' => '',
        'exec' => '',
        'className' => \GeorgRinger\OidcBe\Service\OidcBeAuthService::class,
    ]
);