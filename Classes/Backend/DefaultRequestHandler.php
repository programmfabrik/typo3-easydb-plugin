<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Backend;

use Easydb\Typo3Integration\Controller\ImportFilesController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

/**
 * Checks if backend user is authenticated (client must sent the request "withCredentials")
 * and calls the controller in that case
 */
class DefaultRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly FormProtectionFactory $formProtectionFactory,
        private readonly ImportFilesController $importFilesController
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return $this->context->getPropertyFromAspect('backend.user', 'id') > 0
            && $this->formProtectionFactory->createFromRequest($request)->validateToken(
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
