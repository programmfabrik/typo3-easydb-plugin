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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Adds a button for importing files from easydb to the file list module
 */
class FileListButtonHook
{
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

    public function __construct(IconFactory $iconFactory = null, LanguageService $languageService = null, UriBuilder $uriBuilder = null)
    {
        $this->iconFactory = $iconFactory ?: GeneralUtility::makeInstance(IconFactory::class);
        $this->languageService = $languageService ?: $GLOBALS['LANG'];
        $this->uriBuilder = $uriBuilder ?: GeneralUtility::makeInstance(UriBuilder::class);
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
        $buttons[ButtonBar::BUTTON_POSITION_LEFT][] = [];
        $buttonBarIndex = count($buttons[ButtonBar::BUTTON_POSITION_LEFT]);

        $button = $buttonBar->makeLinkButton();
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
        $button->setTitle($this->languageService->sL('LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:addFiles'));
        $button->setHref($this->getWindowOpenJs());

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][$buttonBarIndex][] = $button;

        return $buttons;
    }

    private function getWindowOpenJs()
    {
        // Encoding galore
        $filePickerArgument = \rawurlencode(\base64_encode(\json_encode(
            [
                'callbackurl' => $this->getCallBackUrl(),
                'extensions' => $this->getAllowedFileExtensions(),
            ]
        )));
        $windowSize = $this->getWindowSize();

        return <<<EOF
javascript:window.open("http://master.5.easydb.de/?typo3filepicker=$filePickerArgument",
    "easydb_picker",
    "width=${windowSize['width']},height=${windowSize['height']},status=0,menubar=0,resizable=1,location=0,directories=0,scrollbars=1,toolbar=0"
);
EOF;
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @return string
     */
    private function getCallBackUrl()
    {
        return (string)$this->uriBuilder->buildUriFromRoute(
            'ajax_easydb_import',
            [
                'id' => isset($_GET['id']) ? $_GET['id'] : $this->getRootLevelFolder(),
            ],
            UriBuilder::ABSOLUTE_URL
        );
    }

    /**
     * TODO: take allowed file extensions from user permissions
     *
     * @return array
     */
    private function getAllowedFileExtensions()
    {
        return ['jpg', 'tif', 'png'];
    }

    /**
     * TODO: make window size configurable
     *
     * @return array
     */
    private function getWindowSize()
    {
        return [
            'width' => 650,
            'height' => 600,
        ];
    }

    /**
     * TODO: FIXME We need to look up the root level folder like file list module does
     *
     * @return string
     */
    private function getRootLevelFolder()
    {
        return '1:/';
    }
}
