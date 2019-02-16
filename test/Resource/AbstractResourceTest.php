<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Resource\AbstractResource;
use KynxTest\Saiku\Client\AbstractTest;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\AbstractResource
 */
class AbstractResourceTest extends AbstractTest
{
    /**
     * @covers ::__construct
     * @covers ::decodeResponse
     */
    public function testDecodeResponse()
    {
        $instance = new class($this->getSessionResource()) extends AbstractResource {
            public function callDecodeResponse(ResponseInterface $r)
            {
                return $this->decodeResponse($r);
            }
        };

        $response = new Response(200, [], '{"foo":"bar"}');
        $actual   = $instance->callDecodeResponse($response);
        $this->assertEquals(['foo' => 'bar'], $actual);
    }
}
