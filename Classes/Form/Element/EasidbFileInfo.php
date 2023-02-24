<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Form\Element;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EasidbFileInfo extends AbstractNode
{
    private const fieldsToRender = [
        'easydb_system_object_id',
        'easydb_objecttype',
        'easydb_asset_id',
        'easydb_asset_version',
        'easydb_object_id',
        'easydb_object_version',
        'easydb_uuid',
        'easydb_uid',
    ];

    /**
     * Renders additional fields from easydb
     * @return array<string, mixed>
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $fileUid = 0;
        if ($this->data['tableName'] === 'sys_file') {
            $fileUid = (int)$this->data['databaseRow']['uid'];
        } elseif ($this->data['tableName'] === 'sys_file_metadata') {
            $fileUid = (int)$this->data['databaseRow']['file'][0];
        }

        if ($fileUid <= 0) {
            return $resultArray;
        }
        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);

        $resultArray['html'] = $this->renderFileInformationContent($fileObject);

        return $resultArray;
    }

    private function renderFileInformationContent(AbstractFile $file): string
    {
        $renderedHtml = '<br>';
        $fileProperties = $file->getProperties();
        foreach (self::fieldsToRender as $fieldName) {
            $label = $GLOBALS['TCA']['sys_file']['columns'][$fieldName]['label'];
            $value = $fileProperties[$fieldName];
            $renderedHtml .= sprintf('<strong>%s:</strong> %s<br>', $label, $value);
        }

        return $renderedHtml;
    }
}
