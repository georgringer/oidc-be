<?php

// Backend login provider
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1710277788] = [
    'provider' => \GeorgRinger\OidcBe\Backend\OidcLoginProvider::class,
    'sorting' => 50,
    'iconIdentifier' => 'ext-oidcbe-icon',
    'label' => 'Login with OpenID Connect BE',
];


$settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('oidc') ?? [];

// Service configuration
$subTypesArr = [];
$subTypesArr[] = 'getUserBE';
$subTypesArr[] = 'authUserBE';
$subTypesArr[] = 'getGroupsBE';
$subTypesArr[] = 'processLoginDataBE';
$subTypesArr[] = 'processLoginData';
$subTypes = implode(',', $subTypesArr);

$authenticationClassName = \GeorgRinger\OidcBe\Service\OidcBeAuthService::class;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'oidc_be',
    'auth',
    $authenticationClassName,
    [
        'title' => 'Authentication service',
        'description' => 'Authentication service for OpenID Connect BE',
        'subtype' => $subTypes,
        'available' => true,
        'priority' => (int)($settings['authenticationServicePriority'] ?? 30),
        'quality' => (int)($settings['authenticationServiceQuality'] ?? 80),
        'os' => '',
        'exec' => '',
        'className' => $authenticationClassName,
    ]
);