<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Model;

use DateTimeImmutable;
use Kynx\Saiku\Exception\HydrationException;
use Kynx\Saiku\Entity\SaikuLicense;
use PHPUnit\Framework\TestCase;

class SaikuLicenseTest extends TestCase
{
    public function testConstructorHydratesExpiration()
    {
        $json = '{"expiration":"2019-01-28T14:03:39+00:00"}';
        $actual = new SaikuLicense($json);
        $this->assertInstanceOf(DateTimeImmutable::class, $actual->getExpiration());
    }

    public function testConstructorHydratesNullExpiration()
    {
        $json = '{"expiration":""}';
        $actual = new SaikuLicense($json);
        $this->assertNull($actual->getExpiration());
    }

    public function testConstructorThrowsHydrationException()
    {
        $this->expectException(HydrationException::class);
        $json = '{"expiration":"blah"}';
        new SaikuLicense($json);
    }

    public function testToArrayExtractsExpiration()
    {
        $expiration = new DateTimeImmutable("2019-01-28T14:03:39+00:00");
        $license = new SaikuLicense();
        $license->setExpiration($expiration);
        $actual = $license->toArray();
        $this->assertEquals("2019-01-28T14:03:39+00:00", $actual['expiration']);
    }
}
