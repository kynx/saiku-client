<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\LicenseResource;
use Kynx\Saiku\Client\Resource\SessionResource;
use KynxTest\Saiku\Client\AbstractTest;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\LicenseResource
 */
final class LicenseResourceTest extends AbstractTest
{
    /**
     * @var LicenseResource
     */
    private $license;
    /**
     * @var SessionResource
     */
    private $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getSessionResource();
        $this->license = new LicenseResource($this->session, $this->client);
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $license = '{
            "email":"matt@kynx.org",
            "expiration":null,
            "licenseNumber":"0",
            "licenseType":"community_edition",
            "name":"kynx.org",
            "version":"3",
            "memoryLimit":0,
            "hostname":"localhost",
            "userLimit":0
        }';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], $license)
        ]);
        $actual = $this->license->get();
        $expected = new License($license);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::get
     */
    public function testGet500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500)
        ]);
        $this->license->get();
    }

    /**
     * @covers ::set
     */
    public function testSet()
    {
        $this->mockResponses([
            new Response(200)
        ]);
        $this->license->set($this->getStream('foo'));
        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('foo', (string) $request->getBody());
    }

    /**
     * @covers ::set
     */
    public function testSetSendsAuthorizationHeader()
    {
        $this->mockResponses([
            new Response(200)
        ]);
        $this->license->set($this->getStream('foo'));
        $request = $this->getLastRequest();
        $expected = 'Basic ' . base64_encode($this->session->getUsername() . ":" . $this->session->getPassword());
        $this->assertEquals([$expected], $request->getHeader('Authorization'));
    }

    /**
     * @covers ::set
     */
    public function testSet500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            new Response(500)
        ]);
        $this->license->set($this->getStream('foo'));
    }

    public function testSet401ThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->mockResponses([
            new Response(401)
        ]);
        $this->license->set($this->getStream('foo'));
    }

    private function getStream(string $contents): Stream
    {
        $fh = fopen('php://memory', 'w');
        fputs($fh, $contents);
        return new Stream($fh);
    }
}
