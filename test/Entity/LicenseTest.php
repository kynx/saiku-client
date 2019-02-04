<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use DateTimeImmutable;
use Kynx\Saiku\Client\Exception\HydrationException;
use Kynx\Saiku\Client\Entity\License;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\License
 */
class LicenseTest extends TestCase
{
    /**
     * @var License
     */
    private $license;

    protected function setUp()
    {
        $this->license = new License();
    }

    /**
     * @covers ::hydrate
     */
    public function testHydratePopulatesExpiration()
    {
        $json = '{"expiration":"2019-01-28T14:03:39+00:00"}';
        $actual = new License($json);
        $this->assertInstanceOf(DateTimeImmutable::class, $actual->getExpiration());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydratesPopulatesNullExpiration()
    {
        $json = '{"expiration":""}';
        $actual = new License($json);
        $this->assertNull($actual->getExpiration());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrationBadExpirationThrowsHydrationException()
    {
        $this->expectException(HydrationException::class);
        $json = '{"expiration":"blah"}';
        new License($json);
    }

    /**
     * @covers ::extract
     */
    public function testExtractFormatsExpiration()
    {
        $expiration = new DateTimeImmutable("2019-01-28T14:03:39+00:00");
        $license = new License();
        $license->setExpiration($expiration);
        $actual = $license->toArray();
        $this->assertEquals("2019-01-28T14:03:39+00:00", $actual['expiration']);
    }

    /**
     * @covers ::setExpiration
     * @covers ::getExpiration
     */
    public function testSetExpiration()
    {
        $expiration = new DateTimeImmutable("2019-01-28T14:03:39+00:00");
        $this->license->setExpiration($expiration);
        $this->assertEquals($expiration, $this->license->getExpiration());
    }

    /**
     * @covers ::setVersion
     * @covers ::getVersion
     */
    public function testSetVersion()
    {
        $this->license->setVersion('1.1');
        $this->assertEquals('1.1', $this->license->getVersion());
    }

    /**
     * @covers ::setEmail
     * @covers ::getEmail
     */
    public function testSetEmail()
    {
        $this->license->setEmail('slarty@fjords.no');
        $this->assertEquals('slarty@fjords.no', $this->license->getEmail());
    }

    /**
     * @covers ::setLicenseType
     * @covers ::getLicenseType
     */
    public function testSetLicenseType()
    {
        $this->license->setLicenseType('COMMUNITY');
        $this->assertEquals('COMMUNITY', $this->license->getLicenseType());
    }

    /**
     * @covers ::setLicenseNumber
     * @covers ::getLicenseNumber
     */
    public function testSetLicenseNumber()
    {
        $this->license->setLicenseNumber('1234');
        $this->assertEquals('1234', $this->license->getLicenseNumber());
    }

    /**
     * @covers ::setName
     * @covers ::getName
     */
    public function testSetName()
    {
        $this->license->setName('Ford Prefect');
        $this->assertEquals('Ford Prefect', $this->license->getName());
    }

    /**
     * @covers ::setHostname
     * @covers ::getHostname
     */
    public function testSetHostname()
    {
        $this->license->setHostname('localhost');
        $this->assertEquals('localhost', $this->license->getHostname());
    }

    /**
     * @covers ::setMemoryLimit
     * @covers ::getMemoryLimit
     */
    public function testSetMemoryLimit()
    {
        $this->license->setMemoryLimit(1024);
        $this->assertEquals(1024, $this->license->getMemoryLimit());
    }
}
