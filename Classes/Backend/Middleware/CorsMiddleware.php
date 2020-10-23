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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This middleware takes care of sending CORS headers
 * for preflight OPTIONS requests before authentication takes place
 * as browsers do not send cookies in preflight requests
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS' && ($corsRequestHandler = GeneralUtility::makeInstance(CorsRequestHandler::class))->canHandleRequest($request)) {
            return $corsRequestHandler->handleRequest($request, new Response());
        }
        $routeName = $request->getQueryParams()['route'] ?? null;
        $easyDbSessionId = $request->getQueryParams()['easydb_ses_id'] ?? null;
        if (is_string($routeName)
            && $routeName === '/ajax/easydb/import'
            && is_string($easyDbSessionId)
            && empty($_COOKIE[BackendUserAuthentication::getCookieName()])
            && $request->getMethod() === 'POST'
            && ($session = new Session())->hasTypo3SessionForEasyDbSession($easyDbSessionId)
        ) {
            $_COOKIE[BackendUserAuthentication::getCookieName()] = $session->fetchTypo3SessionByEasyDbSession($easyDbSessionId);
        }

        return $handler->handle($request);
    }
}
