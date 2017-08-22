<?php
namespace Easydb\Typo3Integration\Tests\Unit\Resource\MetaDataProcessor;

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

use Easydb\Typo3Integration\Persistence\MetaDataProcessor;
use Easydb\Typo3Integration\Persistence\SystemLanguages;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class MetaDataProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function mapsMetaDataCorrectlyDataProvider()
    {
        $locales = [
            'en-US' => 0,
            'de-DE' => 1,
        ];

        $metaData = [
            0 => [
                'uid' => 1,
                'title' => '',
                'keywords' => '',
            ],
            1 => [
                'uid' => 2,
                'title' => '',
                'keywords' => '',
            ],
        ];

        return [
            'Not existing fields are ignored' => [
                $locales,
                $metaData,
                [
                    'title' => 'foo',
                    'foo' => 'bla',
                ],
                [
                    1 => [
                        'title' => 'foo',
                    ],
                ],
            ],
            'Non translated array values are imploded' => [
                $locales,
                $metaData,
                [
                    'title' => 'foo',
                    'keywords' => ['foo', 'bar'],
                ],
                [
                    1 => [
                        'title' => 'foo',
                        'keywords' => 'foo, bar',
                    ],
                ],
            ],
            'Translated values map to multiple metadata records' => [
                $locales,
                $metaData,
                [
                    'title' => [
                        'en-US' => 'title',
                        'de-DE' => 'Titel',
                    ],
                ],
                [
                    1 => [
                        'title' => 'title',
                    ],
                    2 => [
                        'title' => 'Titel',
                    ],
                ],
            ],
            'Translated array values map to multiple metadata records with imploded values' => [
                $locales,
                $metaData,
                [
                    'title' => [
                        'en-US' => 'title',
                        'de-DE' => 'Titel',
                    ],
                    'keywords' => [
                        [
                            'en-US' => 'tag-1',
                            'de-DE' => 'Tag-1',
                        ],
                        [
                            'en-US' => 'tag-2',
                            'de-DE' => 'Tag-2',
                        ],
                    ],
                ],
                [
                    1 => [
                        'title' => 'title',
                        'keywords' => 'tag-1, tag-2',
                    ],
                    2 => [
                        'title' => 'Titel',
                        'keywords' => 'Tag-1, Tag-2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mapsMetaDataCorrectlyDataProvider
     */
    public function mapsMetaDataCorrectly(array $locales, array $metaData, array $payload, array $expected)
    {
        $systemLanguagesProphecy = $this->prophesize(SystemLanguages::class);
        $systemLanguagesProphecy->getLocaleIdMapping()->willReturn($locales);
        $dataHandlerProphecy = $this->prophesize(DataHandler::class);

        $subject = new MetaDataProcessor(
            $metaData,
            $dataHandlerProphecy->reveal(),
            $systemLanguagesProphecy->reveal()
        );

        $result = $subject->mapEsaydbMetaDataToMetaDataRecords($payload);
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider mapsMetaDataCorrectlyDataProvider
     */
    public function createsNewMetaDataRecordWhenItDoesNotExist()
    {
        $locales = [
            'en-US' => 0,
            'de-DE' => 1,
        ];

        $metaData = [
            0 => [
                'uid' => 1,
                'title' => '',
                'keywords' => '',
            ],
        ];

        $systemLanguagesProphecy = $this->prophesize(SystemLanguages::class);
        $systemLanguagesProphecy->getLocaleIdMapping()->willReturn($locales);
        $dataHandlerProphecy = $this->prophesize(DataHandler::class);

        $dataHandlerProphecy->start([], [])->shouldBeCalled();
        $dataHandlerProphecy->localize('sys_file_metadata', 1, 1)->willReturn(2)->shouldBeCalled();

        $subject = new MetaDataProcessor(
            $metaData,
            $dataHandlerProphecy->reveal(),
            $systemLanguagesProphecy->reveal()
        );

        $expected = [
            1 => [
                'title' => 'A title',
            ],
            2 => [
                'title' => 'Ein Titel',
            ],
        ];

        $result = $subject->mapEsaydbMetaDataToMetaDataRecords(
            [
                'title' => [
                    'en-US' => 'A title',
                    'de-DE' => 'Ein Titel',
                ],
            ]
        );

        $this->assertSame($expected, $result);
    }
}
