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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class CorsRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function cannotHandleRequestWhenNoOriginHeaderIsSet()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest();
        $this->assertFalse($subject->canHandleRequest($request));
    }

    /**
     * @test
     */
    public function canHandleRequestWhenOriginHeaderIsSet()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest(
            null,
            null,
            'php://input',
            [
                'origin' => 'http://localhost',
            ]
        );
        $this->assertTrue($subject->canHandleRequest($request));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidRequest()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest();
        $response = new Response();

        $this->assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidOptionsRequest()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest(null, 'OPTIONS');
        $response = new Response();

        $this->assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidMethodRequest()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest(null, 'GET');
        $response = new Response();

        $this->assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsUnmodifiedResponseWithInvalidPostRequest()
    {
        $subject = new CorsRequestHandler();
        $request = new ServerRequest(null, 'POST');
        $response = new Response();

        $this->assertSame($response, $subject->handleRequest($request, $response));
    }

    /**
     * @test
     */
    public function returnsResponseWithCorsHeadersWithValidOptionsRequest()
    {
        $subject = new CorsRequestHandler();
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
        $this->assertNotSame($response, $actualResponse);
        $this->assertTrue($actualResponse->hasHeader('Access-Control-Allow-Origin'));
        $this->assertSame('http://localhost', $actualResponse->getHeader('Access-Control-Allow-Origin')[0]);
    }

    /**
     * @test
     */
    public function returnsResponseWithCorsHeadersWithValidPostRequest()
    {
        $subject = new CorsRequestHandler();
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
        $this->assertNotSame($response, $actualResponse);
        $this->assertTrue($actualResponse->hasHeader('Access-Control-Allow-Origin'));
        $this->assertSame('http://localhost', $actualResponse->getHeader('Access-Control-Allow-Origin')[0]);
    }
}
