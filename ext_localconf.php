<?php
defined('TYPO3_MODE') or die;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['EasydbFileListButton'] =
    \Easydb\Typo3Integration\Hook\FileListButtonHook::class . '->getButtons';
