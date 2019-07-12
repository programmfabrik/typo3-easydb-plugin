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

use Easydb\Typo3Integration\Controller\ImportFilesController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks if backend user is authenticated (client must sent the request "withCredentials")
 * and calls the controller in that case
 */
class DefaultRequestHandler implements RequestHandlerInterface
{
    /**
     * @var BackendUserAuthentication
     */
    private $userAuthentication;

    /**
     * @var AbstractFormProtection
     */
    private $formProtection;

    /**
     * @var ImportFilesController
     */
    private $importFilesController;

    public function __construct(
        BackendUserAuthentication $userAuthentication = null,
        AbstractFormProtection $formProtection = null,
        ImportFilesController $importFilesController = null
    ) {
        $this->userAuthentication = $userAuthentication ?: $GLOBALS['BE_USER'];
        $this->formProtection = $formProtection ?: FormProtectionFactory::get('backend');
        $this->importFilesController = $importFilesController ?: GeneralUtility::makeInstance(ImportFilesController::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return !empty($this->userAuthentication->user['uid'])
            && $this->formProtection->validateToken(
                $request->getQueryParams()['importToken'],
                'easydb',
                'fileImport'
            )
            && $request->getMethod() !== 'OPTIONS';
    }

    public function getPriority()
    {
        return 50;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$this->canHandleRequest($request)) {
            return $response;
        }
        $response = $this->importFilesController->importAction($request, $response);

        return $response->withHeader(
            'Content-Type',
            'application/json; charset=utf-8'
        );
    }
}
