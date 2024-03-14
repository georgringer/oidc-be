<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Backend;

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class OidcLoginProvider implements LoginProviderInterface
{

    protected array $config = [];
    protected string $state = '';

    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oidc') ?? [];
    }

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:oidc_be/Resources/Private/Templates/BackendLogin.html'
            )
        );
        $view->assignMultiple([
            'oidcClientKey' => $this->config['oidcClientKey'] ?? '',
            'redirectUri' => $this->getRedirectUrl(),
            'state' => $this->state,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        return (string)$request->getUri()->withQuery('')->withPath('/typo3');
    }
}
