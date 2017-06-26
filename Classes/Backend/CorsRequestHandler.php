<?php
namespace Easydb\Typo3Integration\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
     * @var ExtensionConfig
     */
    private $config;

    /**
     * Hard coded list of allowed CORS request methods
     *
     * @var array
     */
    private $allowedMethods = ['POST'];

    /**
     * Hard coded list of allowed origin headers
     *
     * @var array
     */
    private $allowedHeaders = ['X-Requested-With'];

    public function __construct(ExtensionConfig $config = null)
    {
        $this->config = $config ?: new ExtensionConfig();
    }

    /**
     * Only send CORS headers if origin header is sent and
     * the origin is allowed
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return $request->hasHeader('origin') && $this->isAllowedOrigin($request->getHeader('origin')[0]);
    }

    public function getPriority()
    {
        return 10;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws \InvalidArgumentException
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response)
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
        $response = $response->withHeader('Access-Control-Allow-Methods', $this->allowedMethods)
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Headers', $this->getAllowedOriginHeaders($allowedOrigin))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '3600');

        return $response;
    }

    /**
     * Pre-flight requests are OPTIONS requests and must have a access-control-request-method header
     * and these methods listed in this header must be allowed
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function isOptionsRequestAllowed(ServerRequestInterface $request)
    {
        return $request->getMethod() !== 'OPTIONS'
            || (
                $request->hasHeader('access-control-request-method')
                && $this->isAllowedRequestMethod($request->getHeader('access-control-request-method')[0])
            );
    }

    /**
     * Regular requests must have an origin header and the current request method needs to be allowed
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function isRequestAllowed(ServerRequestInterface $request)
    {
        return $request->getMethod() === 'OPTIONS' || $this->isAllowedRequestMethod($request->getMethod());
    }

    /**
     * We currently only check against a static list of allowed methods
     *
     * @param string $method
     * @return bool
     */
    private function isAllowedRequestMethod($method)
    {
        return in_array($method, $this->allowedMethods, true);
    }

    /**
     * @param string $origin
     * @return bool
     */
    private function isAllowedOrigin($origin)
    {
        return $this->config->get('allowedOrigin') === $origin;
    }

    /**
     * Currently only return a static list of allowed headers
     *
     * @param string $origin
     * @return array
     */
    private function getAllowedOriginHeaders($origin)
    {
        // TODO: check if we need to allow more headers or distinguish between origins
        return $this->allowedHeaders;
    }
}
