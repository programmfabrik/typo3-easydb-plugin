<?php
$EM_CONF[$_EXTKEY] = [
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
  'version' => '1.0.0',
  'constraints' => [
    'depends' => [
      'typo3' => '7.6.0-8.7.99',
    ],
    'conflicts' => [
    ],
    'suggests' => [
    ],
  ],
];
