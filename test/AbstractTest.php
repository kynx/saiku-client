<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Resource\SessionResource;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

use function end;

abstract class AbstractTest extends TestCase
{
    /** @var Client */
    protected $client;
    /** @var HandlerStack */
    protected $handler;
    /** @var CookieJar */
    protected $cookieJar;
    /** @var array */
    protected $history;

    protected $sessionId = 'DF76204934E41D3E3A930508E57B740D';

    protected function setUp()
    {
        $this->history = [];
        $history       = Middleware::history($this->history);
        $this->handler = HandlerStack::create();
        $this->handler->push($history);

        $this->cookieJar = new CookieJar();
        $options         = [
            'base_uri' => 'http://localhost:9090/saiku/',
            'handler'  => $this->handler,
            'cookies'  => $this->cookieJar,
        ];
        $this->client    = new Client($options);
    }

    protected function getSessionResource() : SessionResource
    {
        $session = new SessionResource($this->client);
        $session->setUsername('foo');
        $session->setPassword('bar');

        return $session;
    }

    protected function getSessionCookie() : SetCookie
    {
        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue($this->sessionId);
        $cookie->setDomain('http://localhost:8080');
        return $cookie;
    }

    protected function mockResponses(array $responses)
    {
        $this->handler->setHandler(new MockHandler($responses));
    }

    protected function getLoginSuccessResponse()
    {
        return new Response(200, ['Set-Cookie' => 'JSESSIONID=' . $this->sessionId . '; Path=/; HttpOnly']);
    }

    protected function getLastRequest() : RequestInterface
    {
        if (empty($this->history)) {
            $this->fail('No request made');
        }
        $last = end($this->history);
        return $last['request'];
    }
}
