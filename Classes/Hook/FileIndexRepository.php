<?php
namespace Easydb\Typo3Integration\Hook;

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

class FileIndexRepository extends \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
{
    public function __construct()
    {
        $this->fields = array_merge(
            $this->fields,
            [
                'easydb_uid',
                'easydb_asset_id',
                'easydb_asset_version',
                'easydb_system_object_id',
                'easydb_objecttype',
                'easydb_object_id',
                'easydb_object_version',
                'easydb_uuid',
            ]
        );
    }
}
