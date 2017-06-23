<?php
namespace Easydb\Typo3Integration\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * That is the entry point for importing files
 * If this is called, a backend user is authenticated
 * so we can perform backend operations here
 */
class ImportFilesController
{
    /**
     * @var ResourceFactory
     */
    private $resourceFactory;

    public function __construct(ResourceFactory $resourceFactory = null)
    {
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function importAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $targetFolderId = $request->getQueryParams()['id'];
        $targetFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($targetFolderId);
        $easyDBRequest = \json_decode($request->getParsedBody()['body'], true);

        $addedFiles = [];

        foreach ($easyDBRequest['files'] as $fileData) {
            $fileContent = file_get_contents($fileData['url']);
            $tempFileName = GeneralUtility::tempnam('easydb_');
            file_put_contents($tempFileName, $fileContent);
            $action = 'insert';
            $duplicationBehavior = DuplicationBehavior::REPLACE;
            if ($targetFolder->hasFile($fileData['filename'])) {
                $action = 'update';
            }
            $uploadedFile = $targetFolder->addFile($tempFileName, $fileData['filename'], $duplicationBehavior);
            $addedFiles[] = [
                'uid' => $fileData['uid'],
                'url' => GeneralUtility::locationHeaderUrl($uploadedFile->getPublicUrl()),
                'resourceid' => $uploadedFile->getUid(),
                'status' => 'done',
                'action_taken' => $action,
            ];
        }

        $easyDBResponse = [
            'files' => $addedFiles,
            'took' => GeneralUtility::milliseconds() - $GLOBALS['PARSETIME_START']
        ];
        $response->getBody()->write(json_encode($easyDBResponse));

        return $response;
    }
}
