<?php
defined('TYPO3_MODE') or die;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['EasydbFileListButton'] =
    \Easydb\Typo3Integration\Hook\FileListButtonHook::class . '->getButtons';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Http\AjaxRequestHandler::class] = ['className' => \Easydb\Typo3Integration\Hook\AjaxRequestHandler::class];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class] = ['className' => \Easydb\Typo3Integration\Hook\FileIndexRepository::class];
