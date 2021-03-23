<?php
namespace Easydb\Typo3Integration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class ExtensionConfig
{
    private static $extensionKey = 'easydb';
    private static $defaults = [
        'allowSessionTransfer' => false,
    ];

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][self::$extensionKey] ?? unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extensionKey]);
        $this->config = array_replace(self::$defaults, $this->config);
        $this->setDerivedConfigOptions();
    }

    public function get($name)
    {
        if (!array_key_exists($name, $this->config)) {
            throw new \InvalidArgumentException(sprintf('Configuration option "%s" does not exist', $name), 1498463301);
        }
        return $this->config[$name];
    }

    private function setDerivedConfigOptions()
    {
        $parsedUrl = parse_url($this->config['serverUrl']);
        $this->config['serverHostName'] = $parsedUrl['host'];
        $this->config['allowedOrigin'] = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $this->config['transferSession'] = $this->needsSessionTransfer($parsedUrl['host']);
    }

    private function needsSessionTransfer($easyDbHostName)
    {
        $sameSiteCookieOption = $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] ?? 'lax';

        return $this->config['allowSessionTransfer']
            && $sameSiteCookieOption !== 'none'
            && $this->siteName($easyDbHostName) !== $this->siteName(GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
    }

    private function siteName($fullHost)
    {
        return implode('.', array_slice(explode('.', $fullHost), -2));
    }
}
