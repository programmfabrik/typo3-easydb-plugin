<?php
namespace Easydb\Typo3Integration\Backend;

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\Backend\HashableSessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Session
{
    public function hasTypo3SessionForEasyDbSession(string $easyDbSessionId): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        return $connection->count(
            'easydb_ses_id',
            'be_sessions',
            ['easydb_ses_id' => $easyDbSessionId]
        ) === 1;
    }

    public function fetchEasyDbSessionByTypo3Session(string $typo3SessionId): string
    {
        if (empty($typo3SessionId)) {
            throw new \UnexpectedValueException('TYPO3 Session is expected to exist', 1603378462);
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        $easyDbSessionId = (string)$connection->select(
            ['easydb_ses_id'],
            'be_sessions',
            ['ses_id' => $this->hashSessionId($typo3SessionId)]
        )->fetchOne();

        if (empty($easyDbSessionId)) {
            $easyDbSessionId = $this->generateEasyDbSessionId();
        }

        return $easyDbSessionId;
    }

    public function fetchTypo3SessionByEasyDbSession(string $easyDbSessionId): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        return (string)$connection->select(
            ['cookie_value'],
            'be_sessions',
            ['easydb_ses_id' => $easyDbSessionId]
        )->fetchOne();
    }

    private function generateEasyDbSessionId(): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');
        $sessionId = (new Random())->generateRandomHexString(32);
        $cookieValue = $GLOBALS['BE_USER']->id ?? '';
        $connection->update(
            'be_sessions',
            [
                'cookie_value' => $cookieValue,
                'easydb_ses_id' => $sessionId,
            ],
            [
                'ses_id' => $this->hashSessionId($cookieValue),
            ]
        );

        return $sessionId;
    }

    private function hashSessionId(string $sessionId): string
    {
        $backend = GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
        if ($backend instanceof HashableSessionBackendInterface) {
            return $backend->hash($sessionId);
        }

        return $sessionId;
    }
}
