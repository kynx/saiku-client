<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\SessionResource;
use Kynx\Saiku\Client\Resource\UserResource;
use KynxTest\Saiku\Client\AbstractTest;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\SessionResource
 */
class SessionResourceTest extends AbstractTest
{
    /**
     * @var SessionResource
     */
    private $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getSessionResource();
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
        $this->mockResponses([new Response(500, [], "Authentication failed for: admin")]);
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
    public function testLogin204ThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([new Response(405)]);
        $this->session->get();
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
            new Response(401)
        ]);
        $this->session->request('GET', UserResource::PATH);
    }
    /**
     * @covers ::clear
     */
    public function testClearClearsCookies()
    {
        $this->mockResponses([
            new Response(200)
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
            new Response(404)
        ]);
        $this->session->get();
        $this->session->clear();
    }
}
