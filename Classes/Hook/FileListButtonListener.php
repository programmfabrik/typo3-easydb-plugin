<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Hook;

use Easydb\Typo3Integration\Backend\Session;
use Easydb\Typo3Integration\ExtensionConfig;
use Easydb\Typo3Integration\Resource\FileUpdater;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds a button for importing files from easydb to the file list module
 */
class FileListButtonListener
{
    /**
     * @var LanguageService
     */
    private $languageService;

    /**
     * @var BackendUserAuthentication
     */
    private $backendUserAuthentication;

    /**
     * @var AbstractFormProtection
     */
    private $formProtection;

    public function __construct(
        private readonly ExtensionConfig $config,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly PageRenderer $pageRenderer,
        private readonly ResourceFactory $resourceFactory,
        LanguageServiceFactory $languageServiceFactory,
        // @todo: this can be injected, when compat to TYPO3 11 is removed
        //        FormProtectionFactory $formProtectionFactory,
    ) {
        $this->backendUserAuthentication = $GLOBALS['BE_USER'];
        $this->languageService = $languageServiceFactory->createFromUserPreferences($this->backendUserAuthentication);
        // @todo: this can be injected, when compat to TYPO3 11 is removed
        //        FormProtectionFactory $formProtectionFactory,
        //        $this->formProtection = $formProtectionFactory;
        $this->formProtection = GeneralUtility::makeInstance(FormProtectionFactory::class)->createForType('backend');
    }

    public function addButton(ModifyButtonBarEvent $event): void
    {
        $buttons = $this->getButtons($event->getButtons());
        $event->setButtons($buttons);
    }

    /**
     * @param array<string, mixed> $buttons
     * @return array<string, mixed>
     * @throws \JsonException
     * @throws RouteNotFoundException
     */
    private function getButtons(array $buttons): array
    {
        if (!$this->isFileListModuleUri()) {
            return $buttons;
        }
        try {
            $button = GeneralUtility::makeInstance(LinkButton::class);
            $button->setShowLabelText(true);
            $button->setIcon($this->iconFactory->getIcon('actions-file-add', Icon::SIZE_SMALL));
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

            $this->pageRenderer->loadJavaScriptModule('@easydb/typo3-integration/easydb-adapter.js');
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][] = [];
            $buttonBarIndex = count($buttons[ButtonBar::BUTTON_POSITION_LEFT]);
            $buttons[ButtonBar::BUTTON_POSITION_LEFT][$buttonBarIndex][] = $button;
        } catch (InsufficientFolderAccessPermissionsException) {

        }

        return $buttons;
    }

    /**
     * @throws InsufficientFolderAccessPermissionsException
     * @throws RouteNotFoundException
     * @throws \JsonException
     */
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
     * @throws InsufficientFolderAccessPermissionsException
     */
    private function getCallBackUrl(): string
    {
        $uriArguments = [
            'id' => $this->combinedIdentifierFromFolder($this->getCurrentFolder()),
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
        return (new Session())->fetchEasyDbSessionByTypo3Session($this->backendUserAuthentication->id ?? '');
    }

    /**
     * @return array{uid: string}[]
     * @throws InsufficientFolderAccessPermissionsException
     */
    private function getExistingFiles(): array
    {
        return (new FileUpdater($this->getCurrentFolder()))->getFilesMap();
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
            $this->backendUserAuthentication->uc['easydb'] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUC']['easydb'];
            $this->backendUserAuthentication->writeUC();
        }
        return $this->backendUserAuthentication->uc['easydb']['windowSize'];
    }

    private function isFileListModuleUri(): bool
    {
        return str_contains(GeneralUtility::getIndpEnv('REQUEST_URI'), '/typo3/module/file/list');
    }

    /**
     * @throws InsufficientFolderAccessPermissionsException
     */
    private function getCurrentFolder(): Folder
    {
        $folderId = $GLOBALS['TYPO3_REQUEST']?->getQueryParams()['id'] ?? $_GET['id'];
        if (isset($folderId)) {
            return $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderId);
        }
        return $this->getRootLevelFolder();
    }

    private function getRootLevelFolder(): Folder
    {
        // Take the first object of the first storage
        $fileStorages = $this->backendUserAuthentication->getFileStorages();
        $fileStorage = current($fileStorages);
        if ($fileStorage instanceof ResourceStorage) {
            return $fileStorage->getRootLevelFolder();
        }
        throw new InsufficientFolderAccessPermissionsException('Could not find any folder to be displayed.', 1498569603);
    }

    private function combinedIdentifierFromFolder(Folder $folder): string
    {
        return sprintf('%d:%s', $folder->getStorage()->getUid(), $folder->getIdentifier());
    }
}
