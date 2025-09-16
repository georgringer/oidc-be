<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Service;

use IMATHUZH\OidcClient\Utility\AuthenticationStatus;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;

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

    public function getUser(): bool|array
    {
        $user = false;
        $request = ServerRequestFactory::fromGlobals();
        $params = $request->getQueryParams();
        $code = $params['code'] ?? null;
        if ($code !== null) {
            $codeVerifier = null;
            if ($this->config['enableCodeVerifier']) {
                $codeVerifier = $this->getCodeVerifierFromSession();
            }
            $user = $this->authenticateWithAuthorizationCode($code, $codeVerifier);
        }

        return $user;
    }

    protected function getMapping(string $table, ServerRequestInterface $request): array
    {
        return [];
    }

}
