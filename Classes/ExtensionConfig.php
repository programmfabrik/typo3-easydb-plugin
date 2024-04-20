<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionConfig
{
    private const extensionKey = 'easydb';
    private const defaults = [
        'allowSessionTransfer' => false,
    ];

    /**
     * @var array<string, bool|string>
     */
    private array $config;

    /**
     * @param array{serverUrl?: string, allowedFileExtensions?: string, defaultLocale?: string, allowSessionTransfer?: bool, transferSession?: bool, serverHostName?: string, allowedOrigin?: string}|null $config
     */
    public function __construct(array $config = null)
    {
        $this->config = $config ?? $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][self::extensionKey] ?? [];
        $this->config = array_replace(self::defaults, $this->config);
        $this->setDerivedConfigOptions();
    }

    /**
     * @return bool|string
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->config)) {
            throw new \InvalidArgumentException(sprintf('Configuration option "%s" does not exist', $name), 1498463301);
        }
        return $this->config[$name];
    }

    private function setDerivedConfigOptions(): void
    {
        $parsedUrl = parse_url((string)$this->config['serverUrl']);
        $this->config['serverHostName'] = $parsedUrl['host'] ?? '';
        $this->config['allowedOrigin'] = ($parsedUrl['scheme'] ?? 'https') . '://' . $this->config['serverHostName'];
        $this->config['transferSession'] = $this->needsSessionTransfer($this->config['serverHostName']);
    }

    private function needsSessionTransfer(string $easyDbHostName): bool
    {
        $sameSiteCookieOption = $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] ?? 'lax';
        $typo3Host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        return (bool)($this->config['allowSessionTransfer'])
            && $sameSiteCookieOption !== 'none'
            && $this->siteName($easyDbHostName) !== $this->siteName($typo3Host);
    }

    private function siteName(string $fullHost): string
    {
        return implode('.', array_slice(explode('.', $fullHost), -2));
    }
}
