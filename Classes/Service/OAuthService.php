<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Service;

use Psr\Http\Message\ServerRequestInterface;

class OAuthService extends \Causal\Oidc\Service\OAuthService
{

    /**
     * Skip TSFE usage
     */
    public function getAuthorizationUrl(?ServerRequestInterface $request, array $options = []): string
    {
        return $this->getProvider()->getAuthorizationUrl($options);
    }
}