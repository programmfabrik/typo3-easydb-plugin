<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Hook;

use Easydb\Typo3Integration\Backend\Session;
use Easydb\Typo3Integration\ExtensionConfig;
use Easydb\Typo3Integration\Resource\FileUpdater;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $this->config = $config ?? new ExtensionConfig();
        $this->iconFactory = $iconFactory ?? GeneralUtility::makeInstance(IconFactory::class);
        $this->languageService = $languageService ?? $GLOBALS['LANG'];
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->pageRenderer = $pageRenderer ?? GeneralUtility::makeInstance(PageRenderer::class);
        $this->resourceFactory = $resourceFactory ?? GeneralUtility::makeInstance(ResourceFactory::class);
        $this->backendUserAuthentication = $backendUserAuthentication ?? $GLOBALS['BE_USER'];
        $this->formProtection = $formProtection ?? FormProtectionFactory::get();
    }

    /**
     * @param array{buttons: array<string, mixed>} $params
     * @return array<string, mixed>
     * @throws \JsonException
     * @throws RouteNotFoundException
     */
    public function getButtons(array $params, ButtonBar $buttonBar): array
    {
        $buttons = $params['buttons'];
        // Only add the button to file list module
        if (!$this->isFileListModuleUri()) {
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
                        'config' => \base64_encode(\json_encode([
                            'callbackurl' => $this->getCallBackUrl(),
                            'existing_files' => $this->getExistingFiles(),
                            'extensions' => $this->getAllowedFileExtensions(),
                        ], JSON_THROW_ON_ERROR)),
                        'window' => $this->getWindowSize(),
                    ],
                    JSON_THROW_ON_ERROR
                ),
            ]
        );

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][$buttonBarIndex][] = $button;

        return $buttons;
    }

    private function getTargetUrl(): string
    {
        // Encoding galore
        $serverUrl = rtrim((string)$this->config->get('serverUrl'), '/');
        $parsedUrl = parse_url($serverUrl);
        $filePickerArgument = \rawurlencode(\base64_encode(\json_encode(
            [
                'callbackurl' => $this->getCallBackUrl(),
            ],
            JSON_THROW_ON_ERROR
        )));

        return sprintf(
            $serverUrl . '%stypo3filepicker=%s',
            isset($parsedUrl['query']) ? '&' : '?',
            $filePickerArgument
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws RouteNotFoundException
     */
    private function getCallBackUrl(): string
    {
        $uriArguments = [
            'id' => $_GET['id'] ?? $this->getRootLevelFolder(),
            'importToken' => $this->formProtection->generateToken('easydb', 'fileImport'),
        ];
        if ($this->config->get('transferSession') === true) {
            $uriArguments['easydb_ses_id'] = $this->generateSessionId();
        }

        return (string)$this->uriBuilder->buildUriFromRoute(
            'ajax_easydb_import',
            $uriArguments,
            UriBuilder::ABSOLUTE_URL
        );
    }

    private function generateSessionId(): string
    {
        $typo3SessionId = !empty($GLOBALS['BE_USER']->id) ? $GLOBALS['BE_USER']->id : '';

        return (new Session())->fetchEasyDbSessionByTypo3Session($typo3SessionId);
    }

    /**
     * @return array{uid: string}[]
     */
    private function getExistingFiles(): array
    {
        $folderId = $_GET['id'] ?? $this->getRootLevelFolder();

        return (new FileUpdater($this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderId)))->getFilesMap();
    }

    /**
     * @return string[]
     */
    private function getAllowedFileExtensions(): array
    {
        return GeneralUtility::trimExplode(',', (string)$this->config->get('allowedFileExtensions'));
    }

    /**
     * @return array{height: int, width: int}
     */
    private function getWindowSize(): array
    {
        if (!isset($this->backendUserAuthentication->uc['easydb'])) {
            assert(is_array($this->backendUserAuthentication->uc));
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
        if ($fileStorage instanceof ResourceStorage) {
            return $fileStorage->getUid() . ':' . $fileStorage->getRootLevelFolder()->getIdentifier();
        }
        throw new \RuntimeException('Could not find any folder to be displayed.', 1498569603);
    }

    /**
     * Strange API that requires to query super globals, but that's how it currently is.
     * At least we have an almost clean way to add additional buttons.
     *
     * @return bool
     */
    private function isFileListModuleUri(): bool
    {
        return ($_GET['route'] ?? '') === '/module/file/FilelistList';
    }
}
