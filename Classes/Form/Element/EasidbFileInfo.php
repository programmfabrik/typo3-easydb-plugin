<?php
namespace Easydb\Typo3Integration\Form\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Helmut Hummel <info@helhum.io>
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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class EasidbFileInfo extends AbstractNode
{
    private static $fieldsToRender = [
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
     *
     * @return array
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $fileUid = 0;
        if ($this->data['tableName'] === 'sys_file') {
            $fileUid = (int)$this->data['databaseRow']['uid'];
        } elseif ($this->data['tableName'] === 'sys_file_metadata') {
            $fileUid = (int)$this->data['databaseRow']['file'][0];
        }

        $fileObject = null;
        if ($fileUid <= 0) {
            return $resultArray;
        }
        $fileObject = ResourceFactory::getInstance()->getFileObject($fileUid);

        $resultArray['html'] = $this->renderFileInformationContent($fileObject);

        return $resultArray;
    }

    private function renderFileInformationContent(AbstractFile $file)
    {
        $renderedHtml = '<br>';
        $fileProperties = $file->getProperties();
        foreach (self::$fieldsToRender as $fieldName) {
            $label = $GLOBALS['TCA']['sys_file']['columns'][$fieldName]['label'];
            $value = $fileProperties[$fieldName];
            $renderedHtml .= sprintf('<strong>%s:</strong> %s<br>', $label, $value);
        }

        return $renderedHtml;
    }
}
