<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\ServerRequest;
use Kynx\Saiku\Client\Resource\UserResource;
use Kynx\Saiku\Client\Saiku;
use Psr\Http\Message\ResponseInterface;

/**
 * @group integration
 * @coversNothing
 */
final class SaikuTest extends AbstractIntegrationTest
{
    /**
     * @var Saiku
     */
    private $saiku;

    protected function setUp()
    {
        parent::setUp();
        $this->saiku = new Saiku($this->client);
    }

    public function testProxyReturnsResponse()
    {
        $actual = $this->saiku->proxy(new ServerRequest('GET', UserResource::PATH));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }

    public function testProxyExpiredCookieReturnsResponse()
    {
        $this->cookieJar->setCookie($this->getInvalidSessionCookie());
        $actual = $this->saiku->proxy(new ServerRequest('GET', UserResource::PATH));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }

    private function getInvalidSessionCookie(): SetCookie
    {
        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue('12345678901234567890123456789012');
        $cookie->setDomain($GLOBALS['SAIKU_URL']);
        return $cookie;
    }
}
