<?php
namespace Easydb\Typo3Integration\Persistence;

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

use Easydb\Typo3Integration\Persistence\SystemLanguages;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaDataProcessor
{
    /**
     * @var array
     */
    private $metaData;

    /**
     * @var DataHandler
     */
    private $dataHandler;

    /**
     * @var SystemLanguages
     */
    private $languages;

    public function __construct(array $metaData, DataHandler $dataHandler = null, SystemLanguages $languages = null)
    {
        $this->metaData = $metaData;
        $this->dataHandler = $dataHandler ?: GeneralUtility::makeInstance(DataHandler::class);
        $this->languages = $languages ?: new SystemLanguages();
    }

    public function mapEsaydbMetaDataToMetaDataRecords(array $fileData)
    {
        $metaDataUpdates = [];
        $systemLanguages = $this->languages->getLocaleIdMapping();
        // Only handle meta data fields currently present in TYPO3
        $existingMetaDataFields = array_intersect_key($fileData, $this->metaData[0]);

        // Unset special easydb fields
        unset($existingMetaDataFields['uid'], $existingMetaDataFields['url'], $existingMetaDataFields['filename']);

        foreach ($existingMetaDataFields as $fieldName => $metaDataValue) {
            $metaRecordUid = $this->metaData[0]['uid'];
            $fieldValue = $metaDataValue;
            if (is_array($metaDataValue) && isset($metaDataValue[0])) {
                // we have an array value
                if (!is_array($metaDataValue[0])) {
                    // No locales
                    $metaDataValue = implode(', ', $metaDataValue);
                } else {
                    $arrayValue = $metaDataValue;
                    $metaDataValue = [];
                    $fieldValue = '';
                    foreach ($arrayValue as $singleValue) {
                        foreach ($singleValue as $locale => $value) {
                            $metaDataValue[$locale][] = $value;
                        }
                    }
                }
            }
            if (is_array($metaDataValue)) {
                foreach ($metaDataValue as $locale => $fieldValue) {
                    if (isset($systemLanguages[$locale])) {
                        $languageUid = $systemLanguages[$locale];
                        if (isset($this->metaData[$languageUid])) {
                            $metaRecordUid = $this->metaData[$languageUid]['uid'];
                        } else {
                            $this->dataHandler->start([], []);
                            $metaRecordUid = $this->dataHandler->localize('sys_file_metadata', $this->metaData[0]['uid'], $languageUid);
                            $this->metaData[$languageUid]['uid'] = $metaRecordUid;
                        }
                    } else {
                        $metaRecordUid = $this->metaData[0]['uid'];
                    }
                    if (is_array($fieldValue)) {
                        $fieldValue = implode(', ', $fieldValue);
                    }
                    $metaDataUpdates[$metaRecordUid][$fieldName] = $fieldValue;
                }
            } else {
                if (is_array($fieldValue)) {
                    $fieldValue = implode(', ', $fieldValue);
                }
                $metaDataUpdates[$metaRecordUid][$fieldName] = $fieldValue;
            }
        }

        return $metaDataUpdates;
    }
}
