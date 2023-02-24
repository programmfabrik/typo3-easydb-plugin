<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EasydbRequest
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $files;

    /**
     * @var array{height: int, width: int}
     */
    private array $windowSize;

    /**
     * Validate and request and return normalized object
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function fromServerRequest(ServerRequestInterface $serverRequest): self
    {
        if (!isset($serverRequest->getParsedBody()['body'])) {
            throw new \InvalidArgumentException('Invalid easydb request', 1498561326);
        }
        $parsedBody = \json_decode($serverRequest->getParsedBody()['body'], true, 512, JSON_THROW_ON_ERROR);
        if (empty($parsedBody['files']) || !is_array($parsedBody['files'])) {
            throw new \InvalidArgumentException('Invalid easydb request (empty files)', 1498561365);
        }
        $files = $parsedBody['files'];
        if (!empty($parsedBody['send_data']) && empty($serverRequest->getUploadedFiles()['files'])) {
            throw new \InvalidArgumentException('Invalid easydb request (empty file data)', 1498561655);
        }
        $uploadedFiles = !empty($parsedBody['send_data']) ? (array)$serverRequest->getUploadedFiles()['files'] : [];
        foreach ($files as &$fileData) {
            $localFilePath = GeneralUtility::tempnam('easydb_');
            if (!empty($parsedBody['send_data'])) {
                /** @var UploadedFile $uploadedFile */
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile->getClientFilename() !== $fileData['filename']) {
                        $fileData['error'] = [
                            'code' => 'error.typo3.filename_inconsistent',
                            'parameters' => [
                                'filenameData' => $fileData['filename'],
                                'filenameRequest' => $uploadedFile->getClientFilename(),
                            ],
                            'description' => sprintf('Filename inconsistent. Got "%s" and "%s", but both must be the same.', $fileData['filename'], $uploadedFile->getClientFilename()),
                        ];
                        continue;
                    }
                    file_put_contents($localFilePath, $uploadedFile->getStream()->getContents());
                }
            } else {
                $fileContent = GeneralUtility::getUrl($fileData['url']);
                if (is_string($fileContent) && $fileContent !== '') {
                    file_put_contents($localFilePath, $fileContent);
                } else {
                    $fileData['error'] = [
                        'code' => 'error.typo3.fetch_url_failed',
                        'parameters' => [
                            'url' => $fileData['url'],
                        ],
                        'description' => sprintf('Could not retrieve file contents from URL "%s"', $fileData['url']),
                    ];
                }
            }
            $fileData['local_file'] = $localFilePath;
        }

        return new self($files, $parsedBody['window_preferences']);
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @param array{height: int, width: int} $windowSize
     */
    private function __construct(array $files, array $windowSize)
    {
        $this->files = $files;
        $this->windowSize = $windowSize;
    }

    /**
     * @return array{height: int, width: int}
     */
    public function getWindowSize(): array
    {
        return $this->windowSize;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
