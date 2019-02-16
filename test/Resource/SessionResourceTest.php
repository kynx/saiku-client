<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\SessionResource;
use Kynx\Saiku\Client\Resource\UserResource;
use KynxTest\Saiku\Client\AbstractTest;

use function count;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\SessionResource
 */
class SessionResourceTest extends AbstractTest
{
    /** @var SessionResource */
    private $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getSessionResource();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $session = new SessionResource($this->client);
        $session->setUsername('foo');
        $session->setPassword('bar');
        $this->mockResponses([$this->getLoginSuccessResponse()]);
        $session->get();
        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('username=foo&password=bar', (string) $request->getBody());
    }

    /**
     * @covers ::setUsername
     * @covers ::getUsername
     */
    public function testSetUsername()
    {
        $this->session->setUsername('foo');
        $actual = $this->session->getUsername();
        $this->assertEquals('foo', $actual);
    }

    /**
     * @covers ::getCookieJar
     */
    public function testSetUsernameClearsCookieJar()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->session->setUsername('foo');
        $this->assertEquals(0, count($this->cookieJar));
    }

    /**
     * @covers ::setPassword
     * @covers ::getPassword
     */
    public function testSetPassword()
    {
        $this->session->setPassword('foo');
        $actual = $this->session->getPassword();
        $this->assertEquals('foo', $actual);
    }

    /**
     * @covers ::get
     * @covers ::setUsername
     */
    public function testGetNoUsernameThrowsException()
    {
        $this->expectException(BadLoginException::class);
        $this->session->setUsername('');
        $this->session->get();
    }

    /**
     * @covers ::get
     * @covers ::setPassword
     */
    public function testGetNoPasswordThrowsException()
    {
        $this->expectException(BadLoginException::class);
        $this->session->setPassword('');
        $this->session->get();
    }

    /**
     * @covers ::get
     */
    public function testGetSetsCookie()
    {
        $this->mockResponses([$this->getLoginSuccessResponse()]);

        $this->session->get();
        $cookie = $this->cookieJar->getCookieByName('JSESSIONID');
        $this->assertInstanceOf(SetCookie::class, $cookie);
        $this->assertEquals($this->sessionId, $cookie->getValue());
    }

    /**
     * @covers ::get
     */
    public function testGetAuthenticationFailedThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->mockResponses([new Response(500, [], 'Authentication failed for: admin')]);
        $this->session->setPassword('baz');
        $this->session->get();
    }

    /**
     * @covers ::get
     */
    public function testGet500ThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([new Response(500)]);
        $this->session->setPassword('baz');
        $this->session->get();
    }

    /**
     * @covers ::get
     */
    public function testGet401ThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->mockResponses([new Response(401)]);
        $this->session->get();
    }

    /**
     * @covers ::get
     */
    public function testGetThrowsLicenseException()
    {
        $this->expectException(LicenseException::class);
        $this->mockResponses([new Response(500, [], 'Error fetching license')]);
        $this->session->get();
    }

    /**
     * @covers ::request
     */
    public function testRequestLogsIn()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], 'foo'),
        ]);
        $response = $this->session->request('GET', '/foo');
        $this->assertCount(2, $this->history);
        $this->assertEquals('foo', (string) $response->getBody());
    }

    /**
     * @covers ::request
     */
    public function testRequestThrowsBadLoginExceptionOnSecondAttempt()
    {
        $this->expectException(BadLoginException::class);
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(401),
            $this->getLoginSuccessResponse(),
            new Response(401),
        ]);
        $this->session->request('GET', UserResource::PATH);
    }

    /**
     * @covers ::request
     */
    public function testRequestRethrowsException()
    {
        $this->expectException(ClientException::class);
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([new Response('404')]);
        $this->session->request('GET', '/foo');
    }

    /**
     * @covers ::clear
     */
    public function testClearClearsCookies()
    {
        $this->mockResponses([
            new Response(200),
        ]);

        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue('12345678901234567890123456789012');
        $this->cookieJar->setCookie($cookie);
        $this->session->clear();
        $this->assertEmpty($this->cookieJar->toArray());
    }

    /**
     * @covers ::clear
     */
    public function testClear404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404),
        ]);
        $this->session->get();
        $this->session->clear();
    }
}
