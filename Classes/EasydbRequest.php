<?php
namespace Easydb\Typo3Integration;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EasydbRequest
{
    /**
     * @var array
     */
    private $files;

    /**
     * @var array
     */
    private $windowSize;

    /**
     * Validate and request and return normalized object
     *
     * @param ServerRequestInterface $serverRequest
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return self
     */
    public static function fromServerRequest(ServerRequestInterface $serverRequest)
    {
        if (!isset($serverRequest->getParsedBody()['body'])) {
            throw new \InvalidArgumentException('Invalid easydb request', 1498561326);
        }
        $parsedBody = \json_decode($serverRequest->getParsedBody()['body'], true);
        if (empty($parsedBody['files']) || !is_array($parsedBody['files'])) {
            throw new \InvalidArgumentException('Invalid easydb request (empty files)', 1498561365);
        }
        $files = (array)$parsedBody['files'];
        if (!empty($parsedBody['send_data']) && empty($serverRequest->getUploadedFiles()['files'])) {
            throw new \InvalidArgumentException('Invalid easydb request (empty file data)', 1498561655);
        }
        $uploadedFiles = $parsedBody['send_data'] ? (array)$serverRequest->getUploadedFiles()['files'] : [];
        foreach ($files as &$fileData) {
            $localFilePath = GeneralUtility::tempnam('easydb_');
            if ($parsedBody['send_data']) {
                /** @var UploadedFile $uploadedFile */
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile->getClientFilename() !== $fileData['filename']) {
                        continue;
                    }
                    file_put_contents($localFilePath, $uploadedFile->getStream()->getContents());
                }
            } else {
                file_put_contents($localFilePath, GeneralUtility::getUrl($fileData['url']));
            }
            $fileData['local_file'] = $localFilePath;
        }

        return new self($files, $parsedBody['window_preferences']);
    }

    private function __construct(array $files, array $windowSize)
    {
        $this->files = $files;
        $this->windowSize = $windowSize;
    }

    public function getWindowSize()
    {
        return $this->windowSize;
    }

    public function getFiles()
    {
        return $this->files;
    }
}
