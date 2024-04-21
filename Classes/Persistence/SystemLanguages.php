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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

class SystemLanguages
{
    /**
     * @var array<non-empty-string, SiteLanguage>
     */
    private array $languagesByLocale;
    private string $defaultLanguageLocale = 'en-US';

    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {
    }

    /**
     * @return array<non-empty-string, SiteLanguage>
     */
    public function getLocaleIdMapping(): array
    {
        if (isset($this->languagesByLocale)) {
            return $this->languagesByLocale;
        }
        $backendUser = $GLOBALS['BE_USER'];
        assert($backendUser instanceof BackendUserAuthentication);
        $this->languagesByLocale = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getAvailableLanguages($backendUser) as $languageId => $language) {
                if (!$language->isEnabled()) {
                    continue;
                }
                $locale = $this->extractLocale($language);
                if (isset($this->languagesByLocale[$locale]) && $this->languagesByLocale[$locale]->getLanguageId() !== $language->getLanguageId()) {
                    throw new \LogicException(
                        sprintf(
                            'SiteLanguage with locale "%s" is configured multiple times using different language uids "%d" and "%d"',
                            $locale,
                            $this->languagesByLocale[$locale]->getLanguageId(),
                            $languageId,
                        ),
                        1713694588,
                    );
                }
                if ($languageId === 0) {
                    $this->defaultLanguageLocale = $locale;
                }
                $this->languagesByLocale[$locale] = $language;
            }
        }
        return $this->languagesByLocale;
    }

    public function getDefaultLanguageLocale(): string
    {
        if (!isset($this->languagesByLocale)) {
            // Initialize languages
            $this->getLocaleIdMapping();
        }
        return $this->defaultLanguageLocale;
    }

    /**
     * @param SiteLanguage $language
     * @return non-empty-string
     */
    private function extractLocale(SiteLanguage $language): string
    {
        $locale = $this->normalizeLocale($language);
        if ($locale === '') {
            throw new \LogicException(sprintf('Site Language %d has empty locale configured', $language->getLanguageId()), 1713697035);
        }
        return $locale;
    }

    private function normalizeLocale(SiteLanguage $language): string
    {
        $locale = (string)($language->toArray()['easydbLocale'] ?? '');
        if ($locale !== '') {
            return $locale;
        }
        $typo3Locale = $language->getLocale();
        if ($typo3Locale instanceof Locale) {
            return $typo3Locale->getName();
        }
        return (new Locale($typo3Locale))->getName();
    }
}
