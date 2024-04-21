<?php


$GLOBALS['SiteConfiguration']['site_language']['columns']['easydbLocale'] = [
    'label' => 'LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:label.language.locale',
    'description' => 'LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:label.language.locale.description',
    'config' => [
        'type' => 'input',
        'size' => 20,
        'eval' => 'trim',
        'placeholder' => 'en-US',
    ],
];

$GLOBALS['SiteConfiguration']['site_language']['palettes']['default'] = str_replace(
    'locale,',
    'locale, easydbLocale, --linebreak--,',
    $GLOBALS['SiteConfiguration']['site_language']['palettes']['default']
);
