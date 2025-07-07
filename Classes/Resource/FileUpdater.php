<?php
declare(strict_types=1);
namespace Easydb\Typo3Integration\Resource;

use Easydb\Typo3Integration\Persistence\MetaDataProcessor;
use Easydb\Typo3Integration\Persistence\SystemLanguages;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUpdater
{
    private const filePropertiesToImportMapping = [
        'uid' => 'easydb_uid',
        'asset_id' => 'easydb_asset_id',
        'asset_version' => 'easydb_asset_version',
        'system_object_id' => 'easydb_system_object_id',
        'objecttype' => 'easydb_objecttype',
        'object_id' => 'easydb_object_id',
        'object_version' => 'easydb_object_version',
        'uuid' => 'easydb_uuid',
    ];

    private Folder $targetFolder;

    private DataHandler $dataHandler;

    private RelationHandler $relationHandler;

    /**
     * @var array<string, File>
     */
    private array $files = [];

    /**
     * @var array{uid: string}[]
     */
    private array $filesMap = [];

    public function __construct(
        Folder $targetFolder,
        ?DataHandler $dataHandler = null,
        ?RelationHandler $relationHandler = null
    ) {
        $this->targetFolder = $targetFolder;
        $this->fetchFiles();
        $this->dataHandler = $dataHandler ?? GeneralUtility::makeInstance(DataHandler::class);
        $this->relationHandler = $relationHandler ?? GeneralUtility::makeInstance(RelationHandler::class);
    }

    public function hasFile(string $uid): bool
    {
        return isset($this->files[$uid]);
    }

    private function getFile(string $uid): File
    {
        return $this->files[$uid];
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array{uid: string}[]
     */
    public function getFilesMap(): array
    {
        return $this->filesMap;
    }

    /**
     * @param array<string, mixed> $fileData
     * @return array<string, int|string>
     */
    public function addOrUpdateFile(array $fileData): array
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
            'url' => GeneralUtility::locationHeaderUrl((string)$uploadedFile->getPublicUrl()),
            'resourceid' => $uploadedFile->getUid(),
            'status' => 'done',
            'action_taken' => $action,
        ];
    }

    private function fetchFiles(): void
    {
        foreach ($this->targetFolder->getFiles() as $file) {
            if (is_string($easydbUid = $file->getProperty('easydb_uid')) && $easydbUid !== '') {
                $this->files[$easydbUid] = $file;
                $this->filesMap[] = ['uid' => $easydbUid];
            }
        }
    }

    /**
     * @param File $file
     * @param array<string, mixed> $fileData
     */
    private function addOrUpdateMetaData(File $file, array $fileData): void
    {
        $metaDataProcessor = new MetaDataProcessor(
            $this->getExistingMetaDataRecords($file),
            $this->dataHandler,
            GeneralUtility::makeInstance(SystemLanguages::class)
        );
        $fileProperties = $this->getFileFieldsFromFileData($fileData);
        $fileProperties['storage'] = $file->getStorage()->getUid();
        $this->dataHandler->start(
            [
                'sys_file' => [
                    $file->getUid() => $fileProperties,
                ],
                'sys_file_metadata' => $metaDataProcessor->mapEasydbMetaDataToMetaDataRecords($fileData),
            ],
            []
        );
        $this->dataHandler->isImporting = true;
        $this->dataHandler->process_datamap();
        $this->dataHandler->isImporting = false;
    }

    /**
     * @param array<string, mixed> $fileData
     * @return array<string, mixed>
     */
    private function getFileFieldsFromFileData(array $fileData): array
    {
        $fields = [];
        foreach (self::filePropertiesToImportMapping as $easydbName => $typo3Name) {
            if (isset($fileData[$easydbName])) {
                $fields[$typo3Name] = $fileData[$easydbName];
            }
        }

        return $fields;
    }

    /**
     * @param File $file
     * @return array<int, array<string, scalar>>
     */
    private function getExistingMetaDataRecords(File $file): array
    {
        $this->relationHandler->start(
            (string)$file->getUid(),
            'sys_file_metadata',
            '',
            $file->getUid(),
            'sys_file',
            $GLOBALS['TCA']['sys_file']['columns']['metadata']['config']
        );
        $existingMetaDataRecords = [];
        foreach ($this->relationHandler->getValueArray() as $metaDataUid) {
            $row = BackendUtility::getRecord('sys_file_metadata', $metaDataUid);
            if (!is_array($row)) {
                continue;
            }
            $existingMetaDataRecords[(int)($row['sys_language_uid'] ?? 0)] = $row;
        }
        return $existingMetaDataRecords;
    }
}
