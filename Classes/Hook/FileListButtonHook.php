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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

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

    public function __construct(IconFactory $iconFactory = null, LanguageService $languageService = null)
    {
        $this->iconFactory = $iconFactory ?: GeneralUtility::makeInstance(IconFactory::class);
        $this->languageService = $languageService ?: $GLOBALS['LANG'];
    }

    public function getButtons(array $params, ButtonBar $buttonBar)
    {
        $buttons = $params['buttons'];
        if (!isset($_GET['M']) || 'file_FilelistList' !== $_GET['M']) {
            return $buttons;
        }
        $buttons[ButtonBar::BUTTON_POSITION_LEFT][] = [];
        $buttonBarIndex = count($buttons[ButtonBar::BUTTON_POSITION_LEFT]);
        $url = 'javascript:alert("Not yet implemented")';
        $button = $buttonBar->makeLinkButton();
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
        $button->setTitle($this->languageService->sL('LLL:EXT:easydb/Resources/Private/Language/locallang.xlf:addFiles'));
        $button->setHref($url);
        $buttons[ButtonBar::BUTTON_POSITION_LEFT][$buttonBarIndex][] = $button;

        return $buttons;
    }
}
