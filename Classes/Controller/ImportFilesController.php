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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
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

    public function __construct(ResourceFactory $resourceFactory = null)
    {
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function importAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $targetFolderId = $request->getQueryParams()['id'];
        $targetFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($targetFolderId);
        $requestBody = $request->getParsedBody();
        $easyDBRequest = \json_decode($requestBody['body'], true);
        $easydbUploadedFiles = $request->getUploadedFiles() ? $request->getUploadedFiles()['files'] : [];

        $existingEasydbFiles = [];
        foreach ($targetFolder->getFiles() as $file) {
            if ($easydbUid = $file->getProperty('easydb_uid')) {
                $existingEasydbFiles[$easydbUid] = $file;
            }
        }

        $addedFiles = [];
        foreach ($easyDBRequest['files'] as $fileData) {
            $tempFileName = GeneralUtility::tempnam('easydb_');
            if (empty($easydbUploadedFiles)) {
                $fileContent = GeneralUtility::getUrl($fileData['url']);
                file_put_contents($tempFileName, $fileContent);
            } else {
                /** @var UploadedFile $easydbUploadedFile */
                foreach ($easydbUploadedFiles as $easydbUploadedFile) {
                    if ($easydbUploadedFile->getClientFilename() !== $fileData['filename']) {
                        continue;
                    }
                    $easydbUploadedFile->moveTo($tempFileName);
                }
            }
            $action = 'insert';
            // TODO. handle the case this file has been imported to a different location?
            if (!empty($existingEasydbFiles[$fileData['uid']])) {
                $action = 'update';
                /** @var File $existingFile */
                $existingFile = $existingEasydbFiles[$fileData['uid']];
                $existingFile->getStorage()->replaceFile($existingFile, $tempFileName);
                $existingFile->rename($fileData['filename']);
                $uploadedFile = $existingFile;
            } else {
                $uploadedFile = $targetFolder->addFile($tempFileName, $fileData['filename'], DuplicationBehavior::RENAME);
            }
            $this->addOrUpdateEasydbUid($uploadedFile, $fileData);
            $addedFiles[] = [
                'uid' => $fileData['uid'],
                'url' => GeneralUtility::locationHeaderUrl($uploadedFile->getPublicUrl()),
                'resourceid' => $uploadedFile->getUid(),
                'status' => 'done',
                'action_taken' => $action,
            ];
        }

        $easyDBResponse = [
            'files' => $addedFiles,
            'took' => GeneralUtility::milliseconds() - $GLOBALS['PARSETIME_START'],
        ];
        $response->getBody()->write(json_encode($easyDBResponse));

        return $response;
    }

    private function addOrUpdateEasydbUid(File $file, array $easydbFileData)
    {
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->start(
            $file->getUid(),
            'sys_file_metadata',
            '',
            $file->getUid(),
            'sys_file',
            $GLOBALS['TCA']['sys_file']['columns']['metadata']['config']
        );
        $metaDataRecords = $relationHandler->getValueArray();
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'sys_file' => [
                    $file->getUid() => [
                        'easydb_uid' => $easydbFileData['uid'],
                    ],
                ],
                'sys_file_metadata' => [
                    // TODO handle meta data translations
                    $metaDataRecords[0] => [
                        'title' => $easydbFileData['title'],
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
    }
}
