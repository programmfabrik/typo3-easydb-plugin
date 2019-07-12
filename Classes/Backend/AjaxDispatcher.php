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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxDispatcher
{
    private $requestHandlers = [
        DefaultRequestHandler::class,
        CorsRequestHandler::class,
    ];

    public function dispatchRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        foreach ($this->resolveRequestHandlers($request) as $requestHandler) {
            $response = $requestHandler->handleRequest($request, $response);
        }

        return $response;
    }

    /**
     * Fetches the request handler that suits the best based on the priority and the interface
     *
     * @param ServerRequestInterface $request
     * @throws \TYPO3\CMS\Core\Exception
     * @return RequestHandlerInterface[]
     */
    private function resolveRequestHandlers(ServerRequestInterface $request)
    {
        $suitableRequestHandlers = [];
        foreach ($this->requestHandlers as $requestHandlerClassName) {
            /** @var RequestHandlerInterface $requestHandler */
            $requestHandler = GeneralUtility::makeInstance($requestHandlerClassName);
            if ($requestHandler->canHandleRequest($request)) {
                $priority = $requestHandler->getPriority();
                if (isset($suitableRequestHandlers[$priority])) {
                    throw new \TYPO3\CMS\Core\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176471352);
                }
                $suitableRequestHandlers[$priority] = $requestHandler;
            }
        }
        if (empty($suitableRequestHandlers)) {
            throw new \TYPO3\CMS\Core\Exception('No suitable request handler found.', 1225418233);
        }
        ksort($suitableRequestHandlers);
        return $suitableRequestHandlers;
    }
}
