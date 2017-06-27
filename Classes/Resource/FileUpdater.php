<?php
namespace Easydb\Typo3Integration\Resource;

use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class FileUpdater
{
    /**
     * @var Folder
     */
    private $targetFolder;

    /**
     * @var File[]
     */
    private $files = [];

    public function __construct(Folder $targetFolder)
    {
        $this->targetFolder = $targetFolder;
        $this->fetchFiles();
    }

    public function hasFile($uid)
    {
        return isset($this->files[$uid]);
    }

    private function getFile($uid)
    {
        return $this->files[$uid];
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function addOrUpdateFile($localFilePath, array $fileData)
    {
        $action = 'insert';
        if ($this->hasFile($fileData['uid'])) {
            $action = 'update';
            $existingFile = $this->getFile($fileData['uid']);
            $existingFile->getStorage()->replaceFile($existingFile, $localFilePath);
            $existingFile->rename($fileData['filename']);
            $uploadedFile = $existingFile;
        } else {
            $uploadedFile = $this->targetFolder->addFile($localFilePath, $fileData['filename'], DuplicationBehavior::RENAME);
        }
        $this->addOrUpdateEasydbUid($uploadedFile, $fileData);

        return [
            'uid' => $fileData['uid'],
            'url' => GeneralUtility::locationHeaderUrl($uploadedFile->getPublicUrl()),
            'resourceid' => $uploadedFile->getUid(),
            'status' => 'done',
            'action_taken' => $action,
        ];
    }

    private function fetchFiles()
    {
        foreach ($this->targetFolder->getFiles() as $file) {
            if ($easydbUid = $file->getProperty('easydb_uid')) {
                $this->files[$easydbUid] = $file;
            }
        }
    }

    private function addOrUpdateEasydbUid(File $file, array $fileData)
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
                        'easydb_uid' => $fileData['uid'],
                    ],
                ],
                'sys_file_metadata' => [
                    // TODO handle meta data translations
                    $metaDataRecords[0] => [
                        'title' => $fileData['title'],
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
    }
}
