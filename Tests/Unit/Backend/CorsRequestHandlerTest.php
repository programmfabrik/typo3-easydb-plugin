<?php
namespace Easydb\Typo3Integration\Tests\Unit\Backend;

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

use Easydb\Typo3Integration\Backend\CorsRequestHandler;
use Easydb\Typo3Integration\ExtensionConfig;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CorsRequestHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function cannotHandleRequestWhenNoOriginHeaderIsSet(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest();
        self::assertFalse($subject->canHandleRequest($request));
    }

    /**
     * @test
     */
    public function canHandleRequestWhenOriginHeaderIsSet(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(
            null,
            null,
            'php://input',
            [
                'origin' => 'http://localhost',
            ]
        );
        self::assertTrue($subject->canHandleRequest($request));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest();
        $response = new Response();

        self::assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidOptionsRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(null, 'OPTIONS');
        $response = new Response();

        self::assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidMethodRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(null, 'GET');
        $response = new Response();

        self::assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidPostRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(null, 'POST');
        $response = new Response();

        self::assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsResponseWithCorsHeadersWithValidOptionsRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(
            null,
            'OPTIONS',
            'php://input',
            [
                'origin' => 'http://localhost',
                'access-control-request-method' => 'POST',
            ]
        );
        $response = new Response();
        $actualResponse = $subject->handleRequest($request, $response);
        self::assertNotSame($response, $actualResponse);
        self::assertTrue($actualResponse->hasHeader('Access-Control-Allow-Origin'));
        self::assertSame('http://localhost', $actualResponse->getHeader('Access-Control-Allow-Origin')[0]);
    }

    /**
     * @test
     */
    public function returnsResponseWithCorsHeadersWithValidPostRequest(): void
    {
        $subject = new CorsRequestHandler(new ExtensionConfig(['serverUrl' => 'http://localhost']));
        $request = new ServerRequest(
            null,
            'POST',
            'php://input',
            [
                'origin' => 'http://localhost',
            ]
        );
        $response = new Response();
        $actualResponse = $subject->handleRequest($request, $response);
        self::assertNotSame($response, $actualResponse);
        self::assertTrue($actualResponse->hasHeader('Access-Control-Allow-Origin'));
        self::assertSame('http://localhost', $actualResponse->getHeader('Access-Control-Allow-Origin')[0]);
    }
}
