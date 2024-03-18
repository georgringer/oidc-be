<?php
declare(strict_types=1);

namespace GeorgRinger\OidcBe\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;

class BackendRedirectInFeHandler implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (($queryParams['commandLI'] ?? '') === 'setCookie') {
            return new RedirectResponse('/typo3/');
        }

        return $handler->handle($request);
    }
}
