<?php
$EM_CONF['easydb'] = [
    'title' => 'easydb TYPO3 integration',
    'description' => 'Integration of easydb asset management in TYPO3 CMS',
    'category' => 'backend',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => 'helhum.io',
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.36-12.4.99',
            'php' => '7.4.0-8.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
