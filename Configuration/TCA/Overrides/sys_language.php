<?php
$GLOBALS['TCA']['sys_language']['columns']['easydb_locale'] = [
    'label' => 'LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:label.language.locale',
    'config' => [
        'type' => 'input',
        'size' => 255,
        'eval' => 'trim,required',
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_language', 'easydb_locale', '', 'after:language_isocode');
