<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Backend;

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
        $this->userAuthentication = $userAuthentication ?? $GLOBALS['BE_USER'];
        $this->formProtection = $formProtection ?? FormProtectionFactory::get('backend');
        $this->importFilesController = $importFilesController ?? GeneralUtility::makeInstance(ImportFilesController::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return !empty($this->userAuthentication->user['uid'])
            && $this->formProtection->validateToken(
                $request->getQueryParams()['importToken'],
                'easydb',
                'fileImport'
            )
            && $request->getMethod() !== 'OPTIONS';
    }

    public function getPriority(): int
    {
        return 50;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
