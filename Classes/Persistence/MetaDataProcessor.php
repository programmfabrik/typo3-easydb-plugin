<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Persistence;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MetaDataProcessor
{
    /**
     * @var array<int, array<string, scalar>>
     */
    private array $metaData;

    private DataHandler $dataHandler;

    private SystemLanguages $languages;

    /**
     * @param array<int, array<string, scalar>> $metaData
     */
    public function __construct(array $metaData, DataHandler $dataHandler = null, SystemLanguages $languages = null)
    {
        $this->metaData = $metaData;
        $this->dataHandler = $dataHandler ?? GeneralUtility::makeInstance(DataHandler::class);
        $this->languages = $languages ?? new SystemLanguages();
    }

    /**
     * @param array<string, mixed> $fileData
     * @return array<int, array<string, mixed>>
     */
    public function mapEasydbMetaDataToMetaDataRecords(array $fileData): array
    {
        $metaDataUpdates = [];
        $systemLanguages = $this->languages->getLocaleIdMapping();
        // Only handle meta data fields currently present in TYPO3
        $existingMetaDataFields = array_intersect_key($fileData, $this->metaData[0]);

        // Unset special easydb fields
        unset($existingMetaDataFields['uid'], $existingMetaDataFields['url'], $existingMetaDataFields['filename']);

        $defaultLanguageMetaUid = (int)$this->metaData[0]['uid'];
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

    private function getMetaUidByLanguage(int $languageUid): int
    {
        if (isset($this->metaData[$languageUid])) {
            $metaRecordUid = (int)$this->metaData[$languageUid]['uid'];
        } else {
            $this->dataHandler->start([], []);
            $metaRecordUid = (int)$this->dataHandler->localize('sys_file_metadata', (int)$this->metaData[0]['uid'], $languageUid);
            $this->resetDataHandler();
            $this->metaData[$languageUid]['uid'] = (string)$metaRecordUid;
        }

        return $metaRecordUid;
    }

    /**
     * @param array<mixed>|string $metaDataValue
     * @param string $defaultLocale
     * @return array<string, string>
     */
    public function normalizeSentMetaDataValue($metaDataValue, string $defaultLocale): array
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
        if (isset($metaDataValue[0])) {
            // We have an array value with localizations
            // Resolve those into a simple locale -> string hash map
            $normalizedValue = [];
            foreach ($metaDataValue as $singleValue) {
                foreach ($singleValue as $locale => $value) {
                    $normalizedValue[(string)$locale][] = $value;
                }
            }

            return array_map(
                static function (array $value) {
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
    private function resetDataHandler(): void
    {
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $nestedElementCalls = $cache->get('core-datahandler-nestedElementCalls-');
        unset($nestedElementCalls['localize']['sys_file_metadata'][$this->metaData[0]['uid']]);
        $cache->set('core-datahandler-nestedElementCalls-', $nestedElementCalls);
        $this->dataHandler->copyMappingArray = [];
    }
}
