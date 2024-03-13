<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Backend;

use Causal\Oidc\Event\ModifyResourceOwnerEvent;
use GeorgRinger\OidcBe\Service\OAuthService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\FrontendLogin\Event\ModifyLoginFormViewEvent;

class OidcLoginProvider implements LoginProviderInterface
{

    protected $config = [];
    protected string $state = '';

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oidc') ?? [];

        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:oidc_be/Resources/Private/Templates/BackendLogin.html'
            )
        );
        $view->assign('oidcUri', $this->modifyLoginFormView());
        $view->assign('redirectUri', $this->getRedirectUrl());
        $view->assign('state', $this->state);
        $view->assign('oidcClientKey', $this->config['oidcClientKey'] ?? '');
    }

    protected function getRedirectUrl()
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        return (string)$request->getUri()->withQuery('')->withPath('/typo3');
    }

    public function modifyLoginFormView(): string
    {
        $settings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oidc') ?? [];

        if (empty($settings['oidcClientKey'])
            || empty($settings['oidcClientSecret'])
            || empty($settings['oidcEndpointAuthorize'])
            || empty($settings['oidcEndpointToken'])
        ) {
            return '';
        }

        $requestId = $this->getUniqueId();

        if (session_id() === '') { // If no session exists, start a new one
            session_start();
        }

        if (empty($_SESSION['requestId']) || $_SESSION['requestId'] !== $requestId) {
            $this->prepareAuthorizationUrl($settings);
            $_SESSION['requestId'] = $requestId;
            $_SESSION['oidc_redirect_url'] = GeneralUtility::_GP('redirect_url');
        } else {
        }

        return $_SESSION['oidc_authorization_url'];
    }


    /**
     * Prepares the authorization URL and corresponding expected state (to mitigate CSRF attack)
     * and stores information into the session.
     *
     * @param array $settings
     */
    protected function prepareAuthorizationUrl(array $settings): void
    {
        /** @var OAuthService $service */
        $service = GeneralUtility::makeInstance(OAuthService::class);
        $service->setSettings($settings);
        $authorizationUrl = $service->getAuthorizationUrl();

        // Store the state
        $state = $service->getState();
        $this->state = $state;

//        $this->logger->debug('Generating authorization URL', [
//            'url' => $authorizationUrl,
//            'state' => $state,
//        ]);

        $loginUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        // Sanitize the URL
        $parts = parse_url($loginUrl);
        $queryParts = array_filter(explode('&', $parts['query'] ?? ''), function ($v) {
            [$k,] = explode('=', $v, 2);

            return !in_array($k, ['logintype', 'tx_oidc[code]']);
        });
        $parts['query'] = implode('&', $queryParts);
        $loginUrl = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port']) && !in_array((int)$parts['port'], [80, 443], true)) {
            $loginUrl .= ':' . $parts['port'];
        }
        $loginUrl .= $parts['path'];
        if (!empty($parts['query'])) {
            $loginUrl .= '?' . $parts['query'];
        }

//        $loginUrl .= '&uname=xxx&uident=yyy&status=login';
        $_SESSION['oidc_state'] = $state;
        $_SESSION['oidc_login_url'] = $loginUrl;
        $_SESSION['oidc_authorization_url'] = $authorizationUrl;
    }

    /**
     * Returns a unique ID for the current processed request.
     *
     * This is supposed to be independent of the actual web server (Nginx or Apache) and
     * the way PHP was built and unique enough for our use case, as opposed to using:
     *
     * - zend_thread_id() which requires PHP to be built with Zend Thread Safety - ZTS - support and debug mode
     * - apache_getenv('UNIQUE_ID') which requires Apache as web server and mod_unique_id
     *
     * @return string
     */
    protected function getUniqueId(): string
    {
        return sprintf('%08x', abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
    }
}
