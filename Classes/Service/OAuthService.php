<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Service;

class OAuthService extends \Causal\Oidc\Service\OAuthService
{

    /**
     * Skip TSFE usage
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        return $this->getProvider()->getAuthorizationUrl($options);
    }
}