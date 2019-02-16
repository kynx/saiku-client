<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Exception;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Exception\BadResponseException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Exception\BadResponseException
 */
class BadResponseExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructDefaultsCode()
    {
        $exception = new BadResponseException();
        $this->assertEquals(500, $exception->getCode());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructUsesResponseCode()
    {
        $response  = new Response(201);
        $exception = new BadResponseException('foo', $response);
        $this->assertEquals(201, $exception->getCode());
    }

    /**
     * @covers ::getResponse
     */
    public function testGetResponse()
    {
        $response  = new Response(201, [], 'foo');
        $exception = new BadResponseException('foo', $response);
        $this->assertEquals($response, $exception->getResponse());
    }
}
