<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Backend;

use Easydb\Typo3Integration\ExtensionConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This request handler validates the incoming request
 * and adds appropriate CORS headers to the response if the request is valid
 */
class CorsRequestHandler implements RequestHandlerInterface
{
    /**
     * Hard coded list of allowed CORS request methods
     */
    private const allowedMethods = ['POST'];

    /**
     * Hard coded list of allowed origin headers
     */
    private const allowedHeaders = ['X-Requested-With'];

    public function __construct(private readonly ExtensionConfig $config)
    {
    }

    /**
     * Only send CORS headers if origin header is sent and
     * the origin is allowed
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('origin') && $this->isAllowedOrigin($request->getHeader('origin')[0]);
    }

    public function getPriority(): int
    {
        return 10;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->canHandleRequest($request)) {
            return $response;
        }
        if (!$this->isOptionsRequestAllowed($request)) {
            return $response;
        }

        if (!$this->isRequestAllowed($request)) {
            return $response;
        }

        $allowedOrigin = $request->getHeader('origin')[0];
        return $response->withHeader('Access-Control-Allow-Methods', self::allowedMethods)
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Headers', $this->getAllowedOriginHeaders($allowedOrigin))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '3600');
    }

    /**
     * Pre-flight requests are OPTIONS requests and must have a access-control-request-method header
     * and these methods listed in this header must be allowed
     */
    private function isOptionsRequestAllowed(ServerRequestInterface $request): bool
    {
        return $request->getMethod() !== 'OPTIONS'
            || (
                $request->hasHeader('access-control-request-method')
                && $this->isAllowedRequestMethod($request->getHeader('access-control-request-method')[0])
            );
    }

    /**
     * Regular requests must have an origin header and the current request method needs to be allowed
     */
    private function isRequestAllowed(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' || $this->isAllowedRequestMethod($request->getMethod());
    }

    /**
     * We currently only check against a static list of allowed methods
     */
    private function isAllowedRequestMethod(string $method): bool
    {
        return in_array($method, self::allowedMethods, true);
    }

    private function isAllowedOrigin(string $origin): bool
    {
        return $this->config->get('allowedOrigin') === $origin;
    }

    /**
     * Currently only return a static list of allowed headers
     * @return string[]
     */
    private function getAllowedOriginHeaders(string $origin): array
    {
        return self::allowedHeaders;
    }
}
