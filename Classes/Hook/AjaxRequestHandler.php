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

use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * It is unfortunate that we need this XCLASS, but there is no other way to
 * add public AjaxIds currently.
 * Since we only override the constructor and call the parent, it should be pretty stable
 * for upcoming TYPO3 releases
 */
class AjaxRequestHandler extends \TYPO3\CMS\Backend\Http\AjaxRequestHandler
{
    public function __construct(Bootstrap $bootstrap)
    {
        parent::__construct($bootstrap);
        $this->publicAjaxIds[] = '/ajax/easydb/import';
    }
}
