<?php
return [
    'easydb_import' => [
        'path' => '/easydb/import',
        'target' => \Easydb\Typo3Integration\Backend\AjaxDispatcher::class . '::dispatchRequest',
        'access' => 'public',
    ],
];
