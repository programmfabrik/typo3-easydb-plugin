<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Controller;

use Easydb\Typo3Integration\EasydbRequest;
use Easydb\Typo3Integration\Resource\FileUpdater;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * That is the entry point for importing files
 * If this is called, a backend user is authenticated
 * so we can perform backend operations here
 */
class ImportFilesController
{
    private readonly FlashMessageQueue $messageQueue;

    private readonly BackendUserAuthentication $backendUserAuthentication;

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        FlashMessageService $flashMessageService,
    ) {
        $this->messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $this->backendUserAuthentication = $GLOBALS['BE_USER'];
    }

    public function importAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $importStart = round(microtime(true) * 1000);
        $fileUpdater = new FileUpdater($this->resourceFactory->getFolderObjectFromCombinedIdentifier($request->getQueryParams()['id']));
        try {
            $easydbRequest = EasydbRequest::fromServerRequest($request);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'error' => [
                    'code' => 'error.typo3.request',
                    'description' => $e->getMessage(),
                ],
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $addedFiles = [];
        $userConfig = $this->backendUserAuthentication->uc;
        $userConfig['easydb']['windowSize'] = $easydbRequest->getWindowSize();
        $this->backendUserAuthentication->uc = $userConfig;
        $this->backendUserAuthentication->writeUC();

        foreach ($easydbRequest->getFiles() as $fileData) {
            if (!empty($fileData['error'])) {
                // Error occurred during building the request
                $addedFiles[] = [
                    'uid' => $fileData['uid'],
                    'status' => 'error',
                    'error' => $fileData['error'],
                    'action_taken' => 'insert',
                ];
                continue;
            }
            try {
                $action = 'insert';
                if ($fileUpdater->hasFile($fileData['uid'])) {
                    $action = 'update';
                }
                $addedFiles[] = $fileUpdater->addOrUpdateFile($fileData);
                $this->addFlashMessage($action . 'File', [$fileData['filename']]);
            } catch (\Exception $e) {
                $addedFiles[] = [
                    'uid' => $fileData['uid'],
                    'status' => 'error',
                    'error' => [
                        'code' => 'error.typo3.file_import',
                        'description' => $e->getMessage(),
                    ],
                    'action_taken' => $action,
                ];
            }
        }

        $easyDBResponse = [
            'files' => $addedFiles,
            'took' => round(microtime(true) * 1000) - $importStart,
        ];
        $response->getBody()->write(json_encode($easyDBResponse, JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * @param string[] $arguments
     */
    private function addFlashMessage(string $message, array $arguments = []): void
    {
        $languagePrefix = 'LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:action.';
        $languageTitleSuffix = '.title';
        $this->messageQueue->addMessage(
            new FlashMessage(
                $this->translate($languagePrefix . $message, $arguments),
                $this->translate($languagePrefix . $message . $languageTitleSuffix),
                ContextualFeedbackSeverity::OK,
                true
            )
        );
    }

    /**
     * @param string[] $arguments
     */
    private function translate(string $label, array $arguments = []): string
    {
        if (empty($arguments)) {
            return $GLOBALS['LANG']->sL($label);
        }
        return \vsprintf($GLOBALS['LANG']->sL($label), $arguments);
    }
}
