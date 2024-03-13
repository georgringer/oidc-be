<?php

namespace GeorgRinger\OidcBe\Middleware;

use GeorgRinger\OidcBe\Service\OAuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class OidcRequestHandler implements MiddlewareInterface, LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $p = $request->getParsedBody();
        $p2 = $request->getQueryParams();
//        die('xxx');
        if (($p['oidcProvider'] ?? '') === '1710277788') {

            if (isset($p['code'], $p['state'])) {

            } else {
                $url = $this->modifyLoginFormView();
                $response = new RedirectResponse(
                    $url,
                    307,
                    ['X-Redirect-By' => 'oidc_be']
                );
                return $response;
            }


        } elseif ((($p2['route'] ?? '') === '/login')
            && !isset($p2['uname'])
            && !isset($p2['login_status'])
            && isset($p2['code'], $p2['state'])) {

            $uri = $request->getUri() . '&login_status=login&uname=oidc&uident=oidc';
//            $request = $request->withQueryParams(['login_status' => 'login', 'uname' => 'oidc', 'uident' => 'oidc']);
//            $request = $request->withParsedBody(array_merge((array)$p, ['login_status' => 'login', 'uname' => 'oidc', 'uident' => 'oidc']));
//        DebuggerUtility::var_dump($request);die;
//            echo $request->getUri();die;
            return new RedirectResponse($uri, 307, ['X-Redirect-By' => 'oidc_be']);
        }
        return $handler->handle($request);
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
        $service = GeneralUtility::makeInstance(OAuthService::class);
        $service->setSettings($settings);
        $authorizationUrl = $service->getAuthorizationUrl();

        // Store the state
        $state = $service->getState();

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

        $loginUrl .= '&uname=xxx&uident=yyy&status=login';
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