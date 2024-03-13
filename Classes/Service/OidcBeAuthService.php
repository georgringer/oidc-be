<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class OidcBeAuthService extends \Causal\Oidc\Service\AuthenticationService
{

    public function __construct()
    {
        parent::__construct();
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $this->config['oidcRedirectUri'] = (string)$request->getUri()->withQuery('');
        $this->config['frontendUserMustExistLocally'] = true;
        $this->config['usersStoragePid'] = 0;
    }

    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {
        $get = GeneralUtility::_GET();
        if (isset($get['code'])) {
            $loginData['uname'] = 'oid';
            $loginData['uident'] = 'oid';
            $loginData['uident_text'] = 'oid';
        }
    }
    public function getUser()
    {
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        $user = false;
        $request = ServerRequestFactory::fromGlobals();
        $params = $request->getQueryParams() ;
        $code = $params['code'] ?? null;
        if ($code !== null) {
            $codeVerifier = null;
            if ($this->config['enableCodeVerifier']) {
                $codeVerifier = $this->getCodeVerifierFromSession();
            }
            $user = $this->authenticateWithAuthorizationCode($code, $codeVerifier);
        } else {
            $event = new AuthenticationPreUserEvent($this->login);
            $eventDispatcher->dispatch($event);
            if (!$event->shouldProcess) {
                return false;
            }
            $this->login = $event->loginData;

            $username = $this->login['uname'] ?? null;
            if (isset($this->login['uident_text'])) {
                $password = $this->login['uident_text'];
            } elseif (isset($this->login['uident'])) {
                $password = $this->login['uident'];
            } else {
                $password = null;
            }
            if (!empty($username) && !empty($password)) {
                $user = $this->authenticateWithResourceOwnerPasswordCredentials($username, $password);
            }
        }

        return $user;
    }

    protected function getMapping(string $table): array
    {
        return [];
    }


}