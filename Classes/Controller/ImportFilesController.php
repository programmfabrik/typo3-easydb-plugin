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

use Easydb\Typo3Integration\Resource\FileUpdater;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
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

    public function __construct(ResourceFactory $resourceFactory = null, FlashMessageQueue $messageQueue = null)
    {
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
        $this->messageQueue = $messageQueue ?: GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
    }

    public function importAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $fileUpdater = new FileUpdater($this->resourceFactory->getFolderObjectFromCombinedIdentifier($request->getQueryParams()['id']));
        $easyDBRequest = \json_decode($request->getParsedBody()['body'], true);
        $easydbUploadedFiles = $request->getUploadedFiles() ? $request->getUploadedFiles()['files'] : [];

        $addedFiles = [];
        foreach ($easyDBRequest['files'] as $fileData) {
            $tempFileName = GeneralUtility::tempnam('easydb_');
            if (empty($easydbUploadedFiles)) {
                $fileContent = GeneralUtility::getUrl($fileData['url']);
            } else {
                /** @var UploadedFile $easydbUploadedFile */
                foreach ($easydbUploadedFiles as $easydbUploadedFile) {
                    if ($easydbUploadedFile->getClientFilename() !== $fileData['filename']) {
                        continue;
                    }
                    $fileContent = $easydbUploadedFile->getStream();
                }
            }
            if (!isset($fileContent)) {
                throw new \RuntimeException('Invalid data sent for file ' . $fileData['filename'], 1498474983);
            }
            file_put_contents($tempFileName, $fileContent);
            unset($fileContent);

            $action = 'insert';
            if ($fileUpdater->hasFile($fileData['uid'])) {
                $action = 'update';
            }
            $addedFiles[] = $fileUpdater->addOrUpdateFile($tempFileName, $fileData);
            $this->addFlashMessage($action . 'File', [$fileData['filename']]);
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
