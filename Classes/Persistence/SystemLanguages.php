<?php
namespace Easydb\Typo3Integration\Persistence;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SystemLanguages
{
    /**
     * @var ExtensionConfig
     */
    private $config;

    public function __construct(ExtensionConfig $config = null)
    {
        $this->config = $config ?: new ExtensionConfig();
    }

    public function getLocaleIdMapping()
    {
        if (class_exists(ConnectionPool::class)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
            $languageRecords = $queryBuilder
                ->select('uid', 'easydb_locale')
                ->from('sys_language')
                ->orderBy('sorting')
                ->execute()
                ->fetchAll();
        } else {
            /** @var DatabaseConnection $db */
            $db = $GLOBALS['TYPO3_DB'];
            $languageRecords = $db->exec_SELECTgetRows('uid,easydb_locale', 'sys_language', '1=1' . BackendUtility::deleteClause('sys_language') . BackendUtility::BEenableFields('sys_language'));
        }
        $languagesByIsoCode = [];
        foreach ($languageRecords as $languageRecord) {
            $languagesByIsoCode[$languageRecord['easydb_locale']] = $languageRecord['uid'];
        }

        return $languagesByIsoCode;
    }

    public function getDefaultLanguageLocale()
    {
        return $this->config->get('defaultLocale');
    }
}
