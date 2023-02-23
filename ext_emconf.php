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
  'version' => '1.5.1',
  'constraints' => [
    'depends' => [
      'typo3' => '8.7.32-10.4.99',
      'php' => '7.0.0-7.99.99',
    ],
    'conflicts' => [
    ],
    'suggests' => [
    ],
  ],
];
