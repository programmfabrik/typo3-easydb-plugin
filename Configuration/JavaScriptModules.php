<?php

return [
    'dependencies' => [
        'core',
    ],
    'tags' => [
        'easydb.module',
    ],
    'imports' => [
        '@easydb/typo3-integration/' => [
            'path' => 'EXT:easydb/Resources/Public/JavaScript/',
        ],
    ],
];
