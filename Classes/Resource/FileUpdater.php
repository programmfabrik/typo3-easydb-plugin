<?php
namespace Easydb\Typo3Integration\Resource;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
     * @var DataHandler
     */
    private $dataHandler;

    /**
     * @var RelationHandler
     */
    private $relationHandler;

    /**
     * @var TranslationConfigurationProvider
     */
    private $translationConfigurationProvider;

    /**
     * @var File[]
     */
    private $files = [];

    /**
     * @var array
     */
    private $filesMap = [];

    public function __construct(
        Folder $targetFolder,
        DataHandler $dataHandler = null,
        RelationHandler $relationHandler = null,
        TranslationConfigurationProvider $translationConfigurationProvider = null
    ) {
        $this->targetFolder = $targetFolder;
        $this->fetchFiles();
        $this->dataHandler = $dataHandler ?: GeneralUtility::makeInstance(DataHandler::class);
        $this->relationHandler = $relationHandler ?: GeneralUtility::makeInstance(RelationHandler::class);
        $this->translationConfigurationProvider = $translationConfigurationProvider ?: GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
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

    public function getFilesMap()
    {
        return $this->filesMap;
    }

    public function addOrUpdateFile(array $fileData)
    {
        $action = 'insert';
        if ($this->hasFile($fileData['uid'])) {
            $action = 'update';
            $existingFile = $this->getFile($fileData['uid']);
            $existingFile->getStorage()->replaceFile($existingFile, $fileData['local_file']);
            $existingFile->rename($fileData['filename']);
            $uploadedFile = $existingFile;
        } else {
            $uploadedFile = $this->targetFolder->addFile($fileData['local_file'], $fileData['filename'], DuplicationBehavior::RENAME);
        }
        $this->addOrUpdateMetaData($uploadedFile, $fileData);

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
                $this->filesMap[] = [$easydbUid => $easydbUid];
            }
        }
    }

    private function addOrUpdateMetaData(File $file, array $fileData)
    {
        $this->dataHandler->start(
            [
                'sys_file' => [
                    $file->getUid() => [
                        'easydb_uid' => $fileData['uid'],
                    ],
                ],
                'sys_file_metadata' => $this->mapEsaydbMetaDataToMetaDataRecords($file, $fileData),
            ],
            []
        );
        $this->dataHandler->process_datamap();
    }

    private function mapEsaydbMetaDataToMetaDataRecords(File $file, array $fileData)
    {
        $existingMetaDataRecords = $this->getExistingMetaDataRecords($file);
        $metaDataUpdates = [];
        $systemLanguages = $this->getSystemLanguages();
        // Only handle meta data fields currently present in TYPO3
        $existingMetaDataFields = array_intersect_key($fileData, $existingMetaDataRecords[0]);

        // Unset special easydb fields
        unset($existingMetaDataFields['uid'], $existingMetaDataFields['url'], $existingMetaDataFields['filename']);

        foreach ($existingMetaDataFields as $fieldName => $metaDataValue) {
            $metaRecordUid = $existingMetaDataRecords[0]['uid'];
            $fieldValue = $metaDataValue;
            if (is_array($metaDataValue)) {
                foreach ($metaDataValue as $locale => $easydbValue) {
                    $isoCode = preg_replace('/\-[A-Z]{2}$/', '', $locale);
                    if (isset($systemLanguages[$isoCode])) {
                        $languageUid = $systemLanguages[$isoCode]['uid'];
                        if (!isset($existingMetaDataRecords[$languageUid])) {
                            $this->dataHandler->start([], []);
                            $metaRecordUid = $this->dataHandler->localize('sys_file_metadata', $existingMetaDataRecords[0]['uid'], $languageUid);
                            $existingMetaDataRecords[$languageUid]['uid'] = $metaRecordUid;
                        }
                    }
                    $fieldValue = $easydbValue;
                }
            }
            $metaDataUpdates[$metaRecordUid][$fieldName] = $fieldValue;
        }

        return $metaDataUpdates;
    }

    private function getSystemLanguages()
    {
        // first two keys are "0" (default) and "-1" (multiple), after that comes the "other languages"
        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages();
        $languagesByIsoCode = [];
        foreach ($systemLanguages as $systemLanguage) {
            if ((int)$systemLanguage['uid'] === 0) {
                $isoCode = str_replace('flag-', '', $systemLanguage['flagIcon']);
            } else {
                $isoCode = isset($systemLanguage['language_isocode']) ? $systemLanguage['language_isocode'] : $systemLanguage['ISOcode'];
            }
            $languagesByIsoCode[$isoCode] = $systemLanguage;
        }
        return $languagesByIsoCode;
    }

    private function getExistingMetaDataRecords(File $file)
    {
        $this->relationHandler->start(
            $file->getUid(),
            'sys_file_metadata',
            '',
            $file->getUid(),
            'sys_file',
            $GLOBALS['TCA']['sys_file']['columns']['metadata']['config']
        );
        $existingMetaDataRecords = [];
        foreach ($this->relationHandler->getValueArray() as $metaDataUid) {
            $row = BackendUtility::getRecord('sys_file_metadata', $metaDataUid);
            $existingMetaDataRecords[$row['sys_language_uid']] = $row;
        }
        return $existingMetaDataRecords;
    }
}
