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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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

        $defaultLanguageMetaUid = $this->metaData[0]['uid'];
        $defaultLanguageLocale = $this->languages->getDefaultLanguageLocale();
        foreach ($existingMetaDataFields as $fieldName => $metaDataValue) {
            foreach ($this->normalizeSentMetaDataValue($metaDataValue, $defaultLanguageLocale) as $locale => $fieldValue) {
                // By default use uid of default language meta data record
                $metaRecordUid = $defaultLanguageMetaUid;
                $languageUid = 0;
                if (isset($systemLanguages[$locale])) {
                    $languageUid = $systemLanguages[$locale];
                    $metaRecordUid = $this->getMetaUidByLanguage($languageUid);
                    if ($locale === $defaultLanguageLocale && $GLOBALS['BE_USER']->checkLanguageAccess(0)) {
                        // Additionally expose to default language in case locale matches
                        $metaDataUpdates[$defaultLanguageMetaUid][$fieldName] = $fieldValue;
                    }
                }
                if ($GLOBALS['BE_USER']->checkLanguageAccess($languageUid)) {
                    $metaDataUpdates[$metaRecordUid][$fieldName] = $fieldValue;
                }
            }
        }

        return $metaDataUpdates;
    }

    private function getMetaUidByLanguage($languageUid)
    {
        if (isset($this->metaData[$languageUid])) {
            $metaRecordUid = $this->metaData[$languageUid]['uid'];
        } else {
            $this->dataHandler->start([], []);
            $metaRecordUid = $this->dataHandler->localize('sys_file_metadata', $this->metaData[0]['uid'], $languageUid);
            $this->resetDataHandler();
            $this->metaData[$languageUid]['uid'] = $metaRecordUid;
        }

        return $metaRecordUid;
    }

    public function normalizeSentMetaDataValue($metaDataValue, $defaultLocale)
    {
        if (is_array($metaDataValue) && !isset($metaDataValue[0])) {
            // Default case: A map of locales with their scalar (string) values
            return $metaDataValue;
        }
        if (!is_array($metaDataValue)) {
            // Value is simple type and has no locales
            // Return with default locale
            return [$defaultLocale => $metaDataValue];
        }
        if (isset($metaDataValue[0]) && !is_array($metaDataValue[0])) {
            // We have an array value (e.g. multiple tag names), but no locales
            // Implode the value and handle like simple case
            return $this->normalizeSentMetaDataValue(implode(', ', $metaDataValue), $defaultLocale);
        }
        if (isset($metaDataValue[0]) && is_array($metaDataValue[0])) {
            // We have an array value with localizations
            // Resolve those into a simple locale -> string hash map
            $normalizedValue = [];
            foreach ($metaDataValue as $singleValue) {
                foreach ($singleValue as $locale => $value) {
                    $normalizedValue[$locale][] = $value;
                }
            }

            return array_map(
                function (array $value) {
                    return implode(', ', $value);
                },
                $normalizedValue
            );
        }

        // Something went wrong, but we gracefully proceed
        return [];
    }

    /**
     * This is required to reset the state of the data handler
     * in order to allow to localize records multiple times in one request.
     */
    private function resetDataHandler()
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $nestedElementCalls = $cache->get('core-datahandler-nestedElementCalls-');
        unset($nestedElementCalls['localize']['sys_file_metadata'][$this->metaData[0]['uid']]);
        $cache->set('core-datahandler-nestedElementCalls-', $nestedElementCalls);
        $this->dataHandler->copyMappingArray = [];
    }
}
