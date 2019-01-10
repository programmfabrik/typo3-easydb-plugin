<?php
namespace Easydb\Typo3Integration\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Easydb\Typo3Integration\ExtensionConfig;
use Easydb\Typo3Integration\Resource\FileUpdater;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Adds a button for importing files from easydb to the file list module
 */
class FileListButtonHook
{
    /**
     * @var ExtensionConfig
     */
    private $config;

    /**
     * @var IconFactory
     */
    private $iconFactory;

    /**
     * @var LanguageService
     */
    private $languageService;

    /**
     * @var UriBuilder
     */
    private $uriBuilder;

    /**
     * @var PageRenderer
     */
    private $pageRenderer;

    /**
     * @var ResourceFactory
     */
    private $resourceFactory;

    /**
     * @var BackendUserAuthentication
     */
    private $backendUserAuthentication;

    /**
     * @var AbstractFormProtection
     */
    private $formProtection;

    public function __construct(
        ExtensionConfig $config = null,
        IconFactory $iconFactory = null,
        LanguageService $languageService = null,
        UriBuilder $uriBuilder = null,
        PageRenderer $pageRenderer = null,
        ResourceFactory $resourceFactory = null,
        BackendUserAuthentication $backendUserAuthentication = null,
        AbstractFormProtection $formProtection = null
    ) {
        $this->config = $config ?: new ExtensionConfig();
        $this->iconFactory = $iconFactory ?: GeneralUtility::makeInstance(IconFactory::class);
        $this->languageService = $languageService ?: $GLOBALS['LANG'];
        $this->uriBuilder = $uriBuilder ?: GeneralUtility::makeInstance(UriBuilder::class);
        $this->pageRenderer = $pageRenderer ?: GeneralUtility::makeInstance(PageRenderer::class);
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
        $this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
        $this->formProtection = $formProtection ?: FormProtectionFactory::get();
    }

    public function getButtons(array $params, ButtonBar $buttonBar)
    {
        $buttons = $params['buttons'];
        // Only add the button to file list module
        // Strange API that requires to query super globals, but that's how it currently is
        // At least we have an almost clean way to add additional buttons
        if (!isset($_GET['M']) || 'file_FilelistList' !== $_GET['M']) {
            return $buttons;
        }
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Easydb/EasydbAdapter');
        $buttons[ButtonBar::BUTTON_POSITION_LEFT][] = [];
        $buttonBarIndex = count($buttons[ButtonBar::BUTTON_POSITION_LEFT]);

        $button = $buttonBar->makeLinkButton();
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
        $button->setTitle($this->languageService->sL('LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:button.addFiles'));
        $button->setClasses('button__file-list-easydb');
        $button->setDataAttributes(
            [
                'arguments' => \json_encode(
                    [
                        'targetUrl' => $this->getTargetUrl(),
                        'window' => $this->getWindowSize(),
                    ]
                ),
            ]
        );

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][$buttonBarIndex][] = $button;

        return $buttons;
    }

    private function getTargetUrl()
    {
        // Encoding galore
        $serverUrl = rtrim($this->config->get('serverUrl'), '/');
        $parsedUrl = parse_url($serverUrl);
        $filePickerArgument = \rawurlencode(\base64_encode(\json_encode(
            [
                'callbackurl' => $this->getCallBackUrl(),
                'existing_files' => $this->getExistingFiles(),
                'extensions' => $this->getAllowedFileExtensions(),
            ]
        )));

        return sprintf(
            $serverUrl . '%stypo3filepicker=%s',
            isset($parsedUrl['query']) ? '&' : '?',
            $filePickerArgument
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @return string
     */
    private function getCallBackUrl()
    {
        return (string)$this->uriBuilder->buildUriFromRoute(
            'ajax_easydb_import',
            [
                'id' => isset($_GET['id']) ? $_GET['id'] : $this->getRootLevelFolder(),
                'importToken' => $this->formProtection->generateToken('easydb', 'fileImport'),
            ],
            UriBuilder::ABSOLUTE_URL
        );
    }

    private function getExistingFiles()
    {
        $folderId = isset($_GET['id']) ? $_GET['id'] : $this->getRootLevelFolder();
        return (new FileUpdater($this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderId)))->getFilesMap();
    }

    /**
     * @return array
     */
    private function getAllowedFileExtensions()
    {
        return GeneralUtility::trimExplode(',', $this->config->get('allowedFileExtensions'));
    }

    /**
     * @return array
     */
    private function getWindowSize()
    {
        if (empty($this->backendUserAuthentication->uc['easydb'])) {
            $this->backendUserAuthentication->uc['easydb'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUC']['easydb'];
            $this->backendUserAuthentication->writeUC();
        }
        return $this->backendUserAuthentication->uc['easydb']['windowSize'];
    }

    /**
     * @return string
     */
    private function getRootLevelFolder()
    {
        // Take the first object of the first storage
        $fileStorages = $this->backendUserAuthentication->getFileStorages();
        $fileStorage = current($fileStorages);
        if ($fileStorage) {
            return $fileStorage->getUid() . ':' . $fileStorage->getRootLevelFolder()->getIdentifier();
        }
        throw new \RuntimeException('Could not find any folder to be displayed.', 1498569603);
    }
}
