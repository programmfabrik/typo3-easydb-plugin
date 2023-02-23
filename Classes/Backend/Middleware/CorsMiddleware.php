<?php
namespace Easydb\Typo3Integration\Backend\Middleware;

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

use Easydb\Typo3Integration\Backend\CorsRequestHandler;
use Easydb\Typo3Integration\Backend\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This middleware takes care of sending CORS headers
 * for preflight OPTIONS requests before authentication takes place
 * as browsers do not send cookies in preflight requests
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var CorsRequestHandler|null
     */
    private $corsRequestHandler;

    public function __construct(CorsRequestHandler $corsRequestHandler = null)
    {
        $this->corsRequestHandler = $corsRequestHandler ?? GeneralUtility::makeInstance(CorsRequestHandler::class);
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
        return ($request->getQueryParams()['route'] ?? null) === '/ajax/easydb/import'
            && $this->corsRequestHandler->canHandleRequest($request);
    }
}
