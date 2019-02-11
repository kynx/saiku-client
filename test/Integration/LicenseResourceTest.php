<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Resource\LicenseResource;

/**
 * @group integration
 * @coversNothing
 */
final class LicenseResourceTest extends AbstractIntegrationTest
{
    /**
     * @var LicenseResource
     */
    private $license;

    protected function setUp()
    {
        parent::setUp();
        $this->license = new LicenseResource($this->session, $this->client);
    }

    public function testGet()
    {
        $actual = $this->license->get();
        $this->assertInstanceOf(License::class, $actual);
    }

    public function testSet()
    {
        $fh = fopen($this->getLicenseFile(), 'r');
        $stream = new Stream($fh);
        $this->license->set($stream);

        $actual = $this->license->get();
        $this->assertInstanceOf(License::class, $actual);
    }
}
