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
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\ServerRequest;
use Kynx\Saiku\Exception\BadLoginException;
use Kynx\Saiku\Model\SaikuUser;
use Kynx\Saiku\SaikuClient;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * These tests WILL mess with your saiku repository and users. Use against a development instance!
 *
 * @group integration
 */
class SaikuClientIntegrationTest extends TestCase
{
    private const VALID_USER_ID = 1;
    private const INVALID_USER_ID = 9999;

    /**
     * Set to `true` to dump request and response history for each request
     * @var bool
     */
    private $dump = false;

    /**
     * @var SaikuClient
     */
    private $saiku;
    /**
     * @var CookieJar
     */
    private $cookieJar;
    private $history = [];

    protected function setUp()
    {
        parent::setUp();

        if (! $this->isConfigured()) {
            $this->markTestSkipped("Saiku not configured");
        }

        $this->dump = $GLOBALS['DUMP_HISTORY'] ?? false;

        $this->cookieJar = new CookieJar();
        $history = Middleware::history($this->history);
        $stack = HandlerStack::create();
        $stack->push($history);

        $options = [
            'base_uri' => $GLOBALS['SAIKU_URL'],
            'handler' => $stack,
            'cookies' => $this->cookieJar,
        ];

        $client = new Client($options);
        $this->saiku = new SaikuClient($client);
        $this->saiku->setUsername($GLOBALS['SAIKU_USERNAME'])
            ->setPassword($GLOBALS['SAIKU_PASSWORD']);
    }

    private function isConfigured()
    {
        return isset($GLOBALS['SAIKU_URL']) && isset($GLOBALS['SAIKU_USERNAME']) && isset($GLOBALS['SAIKU_PASSWORD']);
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->dump) {
            printf("%s:\n", $this->getName());
            foreach ($this->history as $transaction) {
                /* @var RequestInterface $request */
                $request = $transaction['request'];
                $body = (string) $request->getBody();
                printf(
                    "%s %s\n%s\n",
                    $request->getMethod(),
                    $request->getUri(),
                    $body ? $body . "\n" : ""
                );


                if (isset($transaction['response'])) {
                    /* @var ResponseInterface $response */
                    $response = $transaction['response'];
                    $headers = [];
                    foreach ($response->getHeaders() as $name => $header) {
                        $headers[] = $name . ': ' . implode(", ", $header);
                    }

                    printf("Status: %s\n", $response->getStatusCode());
                    printf("%s\n\n%s\n\n", join("\n", $headers), (string) $response->getBody());
                } elseif (isset($transaction['error'])) {
                    printf("Error: %s\n\n", $transaction['error']);
                }
            }
        }

    }

    public function testLoginSetsCookie()
    {
        $this->saiku->login();
        $cookie = $this->cookieJar->getCookieByName('JSESSIONID');
        $this->assertInstanceOf(SetCookie::class, $cookie);
        $this->assertRegExp('/[A-Z0-9]{32}/', $cookie->getValue());
    }

    public function testLoginBadPasswordThrowsBadLoginException()
    {
        $this->expectException(BadLoginException::class);
        $this->saiku->setPassword('baz');
        $this->saiku->login();
    }

    public function testLogoutClearsCookies()
    {
        $this->saiku->login();
        $this->saiku->logout();
        $this->assertEmpty($this->cookieJar->toArray());
    }

    public function testProxyReturnsResponse()
    {
        $actual = $this->saiku->proxy(new ServerRequest('GET', $this->saiku::URL_USER));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }

    public function testProxyExpiredCookieReturnsResponse()
    {
        $this->cookieJar->setCookie($this->getInvalidSessionCookie());
        $actual = $this->saiku->proxy(new ServerRequest('GET', $this->saiku::URL_USER));
        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals(200, $actual->getStatusCode());
    }

    public function testGetUserReturnsUser()
    {
        $actual = $this->saiku->getUser(self::VALID_USER_ID);
        $this->assertInstanceOf(SaikuUser::class, $actual);
        $this->assertEquals(self::VALID_USER_ID, $actual->getId());
    }

    public function testGetNonexistentUserReturnsEmpty()
    {
        $actual = $this->saiku->getUser(self::INVALID_USER_ID);
        $this->assertNull($actual);
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

    private function getInvalidSessionCookie(): SetCookie
    {
        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue('12345678901234567890123456789012');
        $cookie->setDomain($GLOBALS['SAIKU_URL']);
        return $cookie;
    }
}
