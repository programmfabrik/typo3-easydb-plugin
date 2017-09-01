<?php
namespace Easydb\Typo3Integration\Controller;

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

use Easydb\Typo3Integration\EasydbRequest;
use Easydb\Typo3Integration\Resource\FileUpdater;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * That is the entry point for importing files
 * If this is called, a backend user is authenticated
 * so we can perform backend operations here
 */
class ImportFilesController
{
    /**
     * @var ResourceFactory
     */
    private $resourceFactory;
    /**
     * @var FlashMessageQueue
     */
    private $messageQueue;

    /**
     * @var BackendUserAuthentication
     */
    private $backendUserAuthentication;

    public function __construct(ResourceFactory $resourceFactory = null, FlashMessageQueue $messageQueue = null, BackendUserAuthentication $backendUserAuthentication = null)
    {
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
        $this->messageQueue = $messageQueue ?: GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
        $this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
    }

    public function importAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $fileUpdater = new FileUpdater($this->resourceFactory->getFolderObjectFromCombinedIdentifier($request->getQueryParams()['id']));
        try {
            $easydbRequest = EasydbRequest::fromServerRequest($request);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => [
                    'code' => 'error.typo3.request',
                    'description' => $e->getMessage(),
                ],
            ];
        }

        $addedFiles = [];

        $this->backendUserAuthentication->uc['easydb']['windowSize'] = $easydbRequest->getWindowSize();
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
            'took' => GeneralUtility::milliseconds() - $GLOBALS['PARSETIME_START'],
        ];
        $response->getBody()->write(json_encode($easyDBResponse));

        return $response;
    }

    private function addFlashMessage($message, array $arguments = [])
    {
        $languagePrefix = 'LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:action.';
        $languageTitleSuffix = '.title';
        $this->messageQueue->addMessage(
            new FlashMessage(
                $this->translate($languagePrefix . $message, $arguments),
                $this->translate($languagePrefix . $message . $languageTitleSuffix),
                FlashMessage::OK,
                true
            )
        );
    }

    private function translate($label, array $arguments = [])
    {
        if (empty($arguments)) {
            return $GLOBALS['LANG']->sL($label);
        }
        return \vsprintf($GLOBALS['LANG']->sL($label), $arguments);
    }
}
