<?php
namespace Easydb\Typo3Integration\Backend;

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Session
{
    public function hasTypo3SessionForEasyDbSession($easyDbSessionId)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        return $connection->count(
            'easydb_ses_id',
            'be_sessions',
            ['easydb_ses_id' => $easyDbSessionId]
        ) === 1;
    }

    public function fetchEasyDbSessionByTypo3Session($typo3SessionId)
    {
        if (empty($typo3SessionId)) {
            throw new \UnexpectedValueException('TYPO3 Session is expected to exist', 1603378462);
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        $easyDbSessionId = (string)$connection->select(
            ['easydb_ses_id'],
            'be_sessions',
            ['ses_id' => $typo3SessionId]
        )->fetchColumn();

        if (empty($easyDbSessionId)) {
            $easyDbSessionId = $this->generateEasyDbSessionId();
        }

        return $easyDbSessionId;
    }

    public function fetchTypo3SessionByEasyDbSession($easyDbSessionId)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');

        return (string)$connection->select(
            ['ses_id'],
            'be_sessions',
            ['easydb_ses_id' => $easyDbSessionId]
        )->fetchColumn();
    }

    private function generateEasyDbSessionId()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_sessions');
        $sessionId = (new Random())->generateRandomHexString(32);
        $connection->update(
            'be_sessions',
            [
                'easydb_ses_id' => $sessionId,
            ],
            [
                'ses_id' => $GLOBALS['BE_USER']->id ?? '',
            ]
        );

        return $sessionId;
    }
}
