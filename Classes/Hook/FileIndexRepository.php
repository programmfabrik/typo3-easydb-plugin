<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Hook;

use TYPO3\CMS\Core\Resource\Index\FileIndexRepository as CoreFileIndexRepositoryAlias;

class FileIndexRepository extends CoreFileIndexRepositoryAlias
{
    /**
     * @var string[]
     */
    protected $fields = [
        'uid', 'pid', 'missing', 'type', 'storage', 'identifier', 'identifier_hash', 'extension',
        'mime_type', 'name', 'sha1', 'size', 'creation_date', 'modification_date', 'folder_hash',
        'easydb_uid', 'easydb_asset_id', 'easydb_asset_version', 'easydb_system_object_id', 'easydb_objecttype', 'easydb_object_id', 'easydb_object_version', 'easydb_uuid',
    ];
}
