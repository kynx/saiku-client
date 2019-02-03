<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Entity;

use DateTimeImmutable;
use Kynx\Saiku\Exception\HydrationException;
use Kynx\Saiku\Entity\License;
use PHPUnit\Framework\TestCase;

class LicenseTest extends TestCase
{
    public function testConstructorHydratesExpiration()
    {
        $json = '{"expiration":"2019-01-28T14:03:39+00:00"}';
        $actual = new License($json);
        $this->assertInstanceOf(DateTimeImmutable::class, $actual->getExpiration());
    }

    public function testConstructorHydratesNullExpiration()
    {
        $json = '{"expiration":""}';
        $actual = new License($json);
        $this->assertNull($actual->getExpiration());
    }

    public function testConstructorThrowsHydrationException()
    {
        $this->expectException(HydrationException::class);
        $json = '{"expiration":"blah"}';
        new License($json);
    }

    public function testToArrayExtractsExpiration()
    {
        $expiration = new DateTimeImmutable("2019-01-28T14:03:39+00:00");
        $license = new License();
        $license->setExpiration($expiration);
        $actual = $license->toArray();
        $this->assertEquals("2019-01-28T14:03:39+00:00", $actual['expiration']);
    }
}
