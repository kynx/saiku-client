<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Integration;

use GuzzleHttp\Cookie\SetCookie;
use Kynx\Saiku\Client\Exception\BadLoginException;

/**
 * @group integration
 * @coversNothing
 */
final class SessionResourceTest extends AbstractIntegrationTest
{
    public function testGetSetsCookie()
    {
        $this->session->get();
        $cookie = $this->cookieJar->getCookieByName('JSESSIONID');
        $this->assertInstanceOf(SetCookie::class, $cookie);
        $this->assertRegExp('/[A-Z0-9]{32}/', $cookie->getValue());
    }

    public function testGetBadPasswordThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->session->setPassword('baz');
        $this->session->get();
    }

    public function testClearClearsCookies()
    {
        $this->session->get();
        $this->session->clear();
        $this->assertEmpty($this->cookieJar->toArray());
    }
}
