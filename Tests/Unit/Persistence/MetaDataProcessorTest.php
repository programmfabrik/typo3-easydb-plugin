<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Helmut Hummel <info@helhum.io>
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
namespace Easydb\Typo3Integration\Tests\Unit\Persistence;

use Easydb\Typo3Integration\Persistence\MetaDataProcessor;
use Easydb\Typo3Integration\Persistence\SystemLanguages;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class MetaDataProcessorTest extends UnitTestCase
{
    /**
     * @return array<string, mixed[]>
     */
    public function normalizeSentMetaDataValueNormalizesAllValuesDataProvider(): array
    {
        return [
            'no locales' => [
                'Title',
                [
                    'en-US' => 'Title',
                ],
            ],
            'no locales, tags' => [
                ['Tag1', 'Tag2'],
                [
                    'en-US' => 'Tag1, Tag2',
                ],
            ],
            'scalar with locales' => [
                ['de-DE' => 'Foo'],
                ['de-DE' => 'Foo'],
            ],
            'tags with locales' => [
                [
                    ['de-DE' => 'Foo'],
                    ['de-DE' => 'Bar'],
                    ['en-US' => 'Bla'],
                    ['en-US' => 'Blupp'],
                ],
                [
                    'de-DE' => 'Foo, Bar',
                    'en-US' => 'Bla, Blupp',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider normalizeSentMetaDataValueNormalizesAllValuesDataProvider
     * @param mixed $inputValue
     * @param mixed $expectedValue
     */
    public function normalizeSentMetaDataValueNormalizesAllValues($inputValue, $expectedValue): void
    {
        $dataHandlerStub = $this->getMockBuilder(DataHandler::class)->disableOriginalConstructor()->getMock();
        $systemLanguagesStub = $this->getMockBuilder(SystemLanguages::class)->disableOriginalConstructor()->getMock();

        $metaDataProcessor = new MetaDataProcessor([], $dataHandlerStub, $systemLanguagesStub);

        self::assertSame($expectedValue, $metaDataProcessor->normalizeSentMetaDataValue($inputValue, 'en-US'));
    }
}
