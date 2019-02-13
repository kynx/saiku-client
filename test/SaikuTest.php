<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\UserResource;
use Kynx\Saiku\Client\Saiku;
use Psr\Http\Message\ResponseInterface;

use function json_decode;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Saiku
 */
class SaikuTest extends AbstractTest
{
    /** @var Saiku */
    private $saiku;

    protected function setUp()
    {
        parent::setUp();

        $this->saiku = new Saiku($this->client);
        $this->saiku->setUsername('foo')
            ->setPassword('bar');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorNoBaseUrlThrowsException()
    {
        $this->expectException(SaikuException::class);
        $options = [
            'cookies' => true,
        ];
        $client  = new Client($options);
        new Saiku($client);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorNoCookiesThrowsException()
    {
        $this->expectException(SaikuException::class);
        $options = [
            'base_uri' => 'http://example.com/saiku',
        ];
        $client  = new Client($options);
        new Saiku($client);
    }

    /**
     * @covers ::withCookieJar
     */
    public function testWithCookieJarReturnInstance()
    {
        $cookieJar = $this->prophesize(CookieJarInterface::class);
        $actual    = $this->saiku->withCookieJar($cookieJar->reveal());
        $this->assertInstanceOf(Saiku::class, $actual);
        $this->assertNotEquals($this->saiku, $actual);
    }

    /**
     * @covers ::proxy
     */
    public function testProxyReturnsResponse()
    {
        $users = '[{
            "username":"admin",
            "email":"test@admin.com",
            "password":"$2a$10$XbOzOjvpUbLJ26uRWR4bWerATU.HYBOsHqL2LXXSGzMBHO9ui7gbq",
            "roles":["ROLE_USER","ROLE_ADMIN"],
            "id":1
        }]';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], $users),
        ]);
        $actual = $this->saiku->proxy(new ServerRequest('GET', UserResource::PATH));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
        $this->assertEquals(json_decode($users, true), json_decode((string) $actual->getBody(), true));
    }

    /**
     * @covers ::proxy
     */
    public function testProxyStripsLeadingSlashFromPath()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], '[]'),
        ]);
        $this->saiku->proxy(new ServerRequest('GET', '/foo'));
        $this->assertCount(1, $this->history);
        /** @var RequestInterface $request */
        $request = $this->history[0]['request'];
        $uri     = $request->getUri();
        $this->assertEquals('/saiku/foo', $uri->getPath());
    }

    /**
     * @covers ::getProxyHeaders
     */
    public function testProxySendsHeaders()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], '[]'),
        ]);
        $this->saiku->proxy(new ServerRequest('GET', '/foo', ['Accept' => 'application/json']));
        $request = $this->getLastRequest();
        $this->assertEquals(['application/json'], $request->getHeader('accept'));
    }

    /**
     * @covers ::getProxyHeaders
     */
    public function testProxyDoesNotSendCookie()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], '[]'),
        ]);
        $this->saiku->proxy(new ServerRequest('GET', '/foo', ['Cookie' => 'foo=bar']));
        $request = $this->getLastRequest();
        $this->assertNotEquals(['foo=bar'], $request->getHeader('cookie'));
    }

    /**
     * @covers ::proxy
     */
    public function testProxyExpiredCookieReturnsResponse()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(401),
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], '[]'),
        ]);
        $actual = $this->saiku->proxy(new ServerRequest('GET', UserResource::PATH));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }
}
