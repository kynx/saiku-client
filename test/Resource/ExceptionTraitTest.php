<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Resource\ExceptionTrait;
use KynxTest\Saiku\Client\AbstractTest;

class ExceptionTraitTest extends AbstractTest
{
    /** @var ExceptionTrait */
    private $instance;

    protected function setUp()
    {
        parent::setUp();

        $this->instance = new class() {
            use ExceptionTrait;

            public function callIsUnAuthorised(GuzzleException $e)
            {
                return $this->isUnauthorisedException($e);
            }

            public function callIsLicense(GuzzleException $e)
            {
                return $this->isLicenseException($e);
            }

            public function callThrowBadLogin(GuzzleException $e, string $username)
            {
                $this->throwBadLoginException($e, $username);
            }

            public function callThrowLicense(GuzzleException $e)
            {
                $this->throwLicenseException($e);
            }
        };
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isUnauthorisedException
     */
    public function testIsUnauthorisedExceptionAuthenticationFailed()
    {
        $exception = new ServerException(
            'Authentication failed',
            new Request('GET', '/foo'),
            new Response(500)
        );
        $actual    = $this->instance->callIsUnAuthorised($exception);
        $this->assertTrue($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isUnauthorisedException
     */
    public function testIsUnauthorisedException401()
    {
        $exception = new ClientException(
            'Authentication failed',
            new Request('GET', '/foo'),
            new Response(401)
        );
        $actual    = $this->instance->callIsUnAuthorised($exception);
        $this->assertTrue($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isUnauthorisedException
     */
    public function testIsUnauthorisedException404()
    {
        $exception = new ClientException(
            'Authentication failed',
            new Request('GET', '/foo'),
            new Response(404)
        );
        $actual    = $this->instance->callIsUnAuthorised($exception);
        $this->assertFalse($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isUnauthorisedException
     */
    public function testIsUnauthorisedExceptionOtherResponse()
    {
        $exception = new TooManyRedirectsException(
            'Some other message',
            new Request('GET', '/foo')
        );
        $actual    = $this->instance->callIsUnAuthorised($exception);
        $this->assertFalse($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isLicenseException
     */
    public function testIsLicenseException()
    {
        $exception = new ServerException(
            'Foo',
            new Request('GET', '/foo'),
            new Response(500, [], 'Error fetching license')
        );
        $actual    = $this->instance->callIsLicense($exception);
        $this->assertTrue($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isLicenseException
     */
    public function testIsLicenseExcpetion500()
    {
        $exception = new ServerException(
            'Foo',
            new Request('GET', '/foo'),
            new Response(500, [], 'Some other message')
        );
        $actual    = $this->instance->callIsLicense($exception);
        $this->assertFalse($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::isLicenseException
     */
    public function testIsLicenseExcpetion404()
    {
        $exception = new ClientException(
            'Foo',
            new Request('GET', '/foo'),
            new Response(404)
        );
        $actual    = $this->instance->callIsLicense($exception);
        $this->assertFalse($actual);
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::throwBadLoginException
     */
    public function testThrowBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $exception = new ServerException('foo', new Request('GET', '/foo'));
        $this->instance->callThrowBadLogin($exception, 'slarty');
    }

    /**
     * @covers \Kynx\Saiku\Client\Resource\ExceptionTrait::throwLicenseException
     */
    public function testThrowLicenseException()
    {
        $this->expectException(LicenseException::class);
        $exception = new ServerException('foo', new Request('GET', '/foo'));
        $this->instance->callThrowLicense($exception);
    }
}
