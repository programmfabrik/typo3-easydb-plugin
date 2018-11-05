<?php
$columns = [
    'easydb_info' => [
        'config' => [
            'type' => 'user',
            'renderType' => 'easidbInfo',
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_file_metadata', 'easydb_info', '', 'after:fileinfo');
