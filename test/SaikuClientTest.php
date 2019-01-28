<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Kynx\Saiku\Exception\BadLoginException;
use Kynx\Saiku\Exception\BadResponseException;
use Kynx\Saiku\Exception\SaikuException;
use Kynx\Saiku\Model\SaikuUser;
use Kynx\Saiku\SaikuClient;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;

class SaikuClientTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var SaikuClient
     */
    private $saiku;
    /**
     * @var HandlerStack
     */
    private $handler;
    /**
     * @var CookieJar
     */
    private $cookieJar;

    private $sessionId = 'DF76204934E41D3E3A930508E57B740D';

    protected function setUp()
    {
        $this->handler = HandlerStack::create();
        $this->cookieJar = new CookieJar();
        $options = [
            'base_uri' => 'http://localhost:9090/saiku',
            'handler' => $this->handler,
            'cookies' => $this->cookieJar,
        ];
        $this->client = new Client($options);
        $this->saiku = new SaikuClient($this->client);
        $this->saiku->setUsername('foo')
            ->setPassword('bar');
    }

    public function testConstructorNoBaseUrlThrowsException()
    {
        $this->expectException(SaikuException::class);
        $options = [
            'cookies' => true,
        ];
        $client = new Client($options);
        new SaikuClient($client);
    }

    public function testConstructorNoCookiesThrowsException()
    {
        $this->expectException(SaikuException::class);
        $options = [
            'base_uri' => 'http://example.com/saiku',
        ];
        $client = new Client($options);
        new SaikuClient($client);
    }

    public function testLoginNoUsernameThrowsException()
    {
        $this->expectException(BadLoginException::class);
        $this->saiku->setUsername('');
        $this->saiku->login();
    }

    public function testLoginNoPasswordThrowsException()
    {
        $this->expectException(BadLoginException::class);
        $this->saiku->setPassword('');
        $this->saiku->login();
    }

    public function testLoginSetsCookie()
    {
        $this->mockResponses([$this->getLoginSuccessResponse()]);

        $this->saiku->login();
        $cookie = $this->cookieJar->getCookieByName('JSESSIONID');
        $this->assertInstanceOf(SetCookie::class, $cookie);
        $this->assertEquals($this->sessionId, $cookie->getValue());
    }

    public function testLoginAuthenticationFailedThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->mockResponses([new Response(500, [], "Authentication failed for: admin")]);
        $this->saiku->setPassword('baz');
        $this->saiku->login();
    }

    public function testLogin500ThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([new Response(500)]);
        $this->saiku->setPassword('baz');
        $this->saiku->login();
    }

    public function testLogin401ThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->mockResponses([new Response(401)]);
        $this->saiku->login();
    }

    public function testLogin204ThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([new Response(405)]);
        $this->saiku->login();
    }

    public function testLogoutClearsCookies()
    {
        $this->mockResponses([
            new Response(200)
        ]);

        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue('12345678901234567890123456789012');
        $this->cookieJar->setCookie($cookie);
        $this->saiku->logout();
        $this->assertEmpty($this->cookieJar->toArray());
    }

    public function testLogout404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404)
        ]);
        $this->saiku->login();
        $this->saiku->logout();
    }

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
            new Response(200, ['Content-Type' => 'application/json'], $users)
        ]);
        $actual = $this->saiku->proxy(new ServerRequest('GET', $this->saiku::URL_USER));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
        $this->assertEquals(json_decode($users, true), json_decode((string) $actual->getBody(), true));
    }

    public function testProxyExpiredCookieReturnsResponse()
    {
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(401),
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], '[]')
        ]);
        $actual = $this->saiku->proxy(new ServerRequest('GET', $this->saiku::URL_USER));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }

    public function testLazyRequestsThrowBadLoginExceptionOnSecondAttempt()
    {
        $this->expectException(BadLoginException::class);
        $this->cookieJar->setCookie($this->getSessionCookie());
        $this->mockResponses([
            new Response(401),
            $this->getLoginSuccessResponse(),
            new Response(401)
        ]);
        $this->saiku->proxy(new ServerRequest('GET', $this->saiku::URL_USER));
    }

    public function testGetUserReturnsUser()
    {
        $user = '{
            "username":"admin",
            "email":"test@admin.com",
            "password":"$2a$10$XbOzOjvpUbLJ26uRWR4bWerATU.HYBOsHqL2LXXSGzMBHO9ui7gbq",
            "roles":["ROLE_USER","ROLE_ADMIN"],
            "id":1
        }';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], $user)
        ]);

        $actual = $this->saiku->getUser(1);
        $this->assertInstanceOf(SaikuUser::class, $actual);
        $this->assertEquals(json_decode($user, true), $actual->toArray());
    }

    public function testGetNonexistentUserReturnsEmpty()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500)
        ]);
        $actual = $this->saiku->getUser(9999);
        $this->assertNull($actual);
    }

    public function testGetUser204ThrowsBadResponseException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(204)
        ]);
        $this->saiku->getUser(1);
    }

//    public function testCreateUserCreates()
//    {
//        $generator = (new Factory())->getLowStrengthGenerator();
//        $user = new SaikuUser();
//        $user->setUsername('foo@test')
//            ->setPassword($generator->generateString(20))
//            ->setEmail('foo@example.com');
//        try {
//            $actual = $this->saiku->createUser($user);
//            $this->assertNotEmpty($actual->getId());
//            $this->assertEquals($user->getUsername(), $actual->getUsername());
//            $this->assertEquals($user->getEmail(), $actual->getEmail());
//        } catch (SaikuException $e) {
//            $this->fail($e->getMessage());
//        } finally {
//            if ($actual && $actual->getId()) {
//                $this->saiku->deleteUser($actual);
//            }
//        }
//    }
//
//    public function testUpdateUser()
//    {
//        $user = $this->saiku->getUser(self::VALID_USER_ID);
//        $this->assertInstanceOf(SaikuUser::class, $user);
//        $oldEmail = $user->getEmail();
//        $oldPassword = $user->getPassword();
//        $this->assertNotEquals('another@example.com', $oldEmail);
//        $user->setEmail('another@example.com');
//
//        try {
//            $actual = $this->saiku->updateUser($user);
//            $this->assertEquals(self::VALID_USER_ID, $actual->getId());
//            $actual = $this->saiku->getUser(self::VALID_USER_ID);
//            $this->assertEquals('another@example.com', $actual->getEmail());
//            $this->assertEquals($oldPassword, $actual->getPassword());
//            $user->setEmail($oldEmail);
//            $this->saiku->updateUser($user);
//        } catch (SaikuException $e) {
//            $this->fail($e->getMessage());
//        }
//    }
//
//    /**
//     * @expectedException \Claritum\Integration\Saiku\Exception\InvalidUserException
//     */
//    public function testUpdateNoIdThrowsException()
//    {
//        $user = new SaikuUser();
//        $user->setUsername('foo@test')
//            ->setPassword('foo');
//        $this->saiku->updateUser($user);
//    }
//
//    /**
//     * @expectedException \Claritum\Integration\Saiku\Exception\InvalidUserException
//     */
//    public function testUpdateNonexistentUserThrowsException()
//    {
//        $user = new SaikuUser();
//        $user->setId(self::INVALID_USER_ID)
//            ->setUsername('foo@test')
//            ->setPassword('foo');
//        $this->saiku->updateUser($user);
//    }
//
//    public function testUpdateUserAndPasswordUpdatesPassword()
//    {
//        $user = $this->saiku->getUser(self::VALID_USER_ID);
//        $this->assertInstanceOf(SaikuUser::class, $user);
//        $oldPassword = $user->getPassword();
//        $user->setPassword('foo');
//
//        try {
//            $actual = $this->saiku->updateUserAndPassword($user);
//            $this->assertEquals(self::VALID_USER_ID, $actual->getId());
//            $this->assertStringStartsWith('$2a$', $actual->getPassword());
//            $this->assertNotEquals($oldPassword, $actual->getPassword());
//        } catch (SaikuException $e) {
//            $this->fail($e->getMessage());
//        } finally {
//            if ($actual) {
//                $user->setPassword($GLOBALS['SAIKU_PASSWORD']);
//                $this->saiku->updateUserAndPassword($user);
//            }
//        }
//    }

    private function mockResponses(array $responses)
    {
        $this->handler->setHandler(new MockHandler($responses));
    }

    private function getLoginSuccessResponse()
    {
        return new Response(200, ['Set-Cookie' => 'JSESSIONID=' . $this->sessionId . '; Path=/; HttpOnly']);
    }

    private function getSessionCookie(): SetCookie
    {
        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue($this->sessionId);
        $cookie->setDomain('http://localhost:8080');
        return $cookie;
    }
}
