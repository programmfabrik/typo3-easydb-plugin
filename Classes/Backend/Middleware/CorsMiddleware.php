<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Backend\Middleware;

use Easydb\Typo3Integration\Backend\CorsRequestHandler;
use Easydb\Typo3Integration\Backend\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;

/**
 * This middleware takes care of sending CORS headers
 * for preflight OPTIONS requests before authentication takes place
 * as browsers do not send cookies in preflight requests
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CorsRequestHandler $corsRequestHandler)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isEasydbRequest($request)) {
            return $handler->handle($request);
        }
        if ($request->getMethod() === 'OPTIONS') {
            return $this->corsRequestHandler->handleRequest($request, new Response());
        }
        $easyDbSessionId = $request->getQueryParams()['easydb_ses_id'] ?? null;
        if (is_string($easyDbSessionId)
            && empty($_COOKIE[BackendUserAuthentication::getCookieName()])
            && $request->getMethod() === 'POST'
            && ($session = new Session())->hasTypo3SessionForEasyDbSession($easyDbSessionId)
        ) {
            $_COOKIE[BackendUserAuthentication::getCookieName()] = $session->fetchTypo3SessionByEasyDbSession($easyDbSessionId);
        }
        $response = null;
        if (empty($_COOKIE[BackendUserAuthentication::getCookieName()])) {
            $response = (new JsonResponse([
                'status' => 'error',
                'error' => [
                    'code' => 'error.typo3.request',
                    'description' => 'No cookie present. Did you configure everything correctly? Are cross site cookies allowed in your browser?',
                ],
            ]))->withStatus(403, 'No cookie present, authentication failed!');
        }

        try {
            $response = $response ?? $handler->handle($request);
        } catch (\Throwable $e) {
            $response = new JsonResponse([
                'status' => 'error',
                'error' => [
                    'code' => 'error.typo3.request',
                    'description' => $e->getMessage(),
                ],
            ]);
        }

        return $this->corsRequestHandler->handleRequest($request, $response);
    }

    private function isEasydbRequest(ServerRequestInterface $request): bool
    {
        $routePath = $request->getQueryParams()['route'] ?? $request->getUri()->getPath();
        return str_ends_with($routePath, '/ajax/easydb/import')
            && $this->corsRequestHandler->canHandleRequest($request);
    }
}
