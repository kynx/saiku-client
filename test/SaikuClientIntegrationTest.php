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
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Exception\BadLoginException;
use Kynx\Saiku\Exception\SaikuExceptionInterface;
use Kynx\Saiku\Exception\UserException;
use Kynx\Saiku\Entity\SaikuLicense;
use Kynx\Saiku\Entity\SaikuUser;
use Kynx\Saiku\SaikuClient;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * These tests WILL mess with your saiku repository and users. Use against a development instance!
 *
 * @group integration
 */
final class SaikuClientIntegrationTest extends TestCase
{
    private const ADMIN_ID = 1;
    private const USER_ID = 2;
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

        $this->loadRepository();
        $this->history = [];
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
        $actual = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertInstanceOf(SaikuUser::class, $actual);
        $this->assertEquals(self::ADMIN_ID, $actual->getId());
    }

    public function testGetNonexistentUserReturnsEmpty()
    {
        $actual = $this->saiku->getUser(self::INVALID_USER_ID);
        $this->assertNull($actual);
    }

    public function testCreateUserCreates()
    {
        $user = new SaikuUser();
        $user->setUsername('foo@test')
            ->setPassword('blahblahblah')
            ->setEmail('foo@example.com');

        try {
            $actual = $this->saiku->createUser($user);
        } finally {
            // if this fails subsequent runs will also fail until you restart docker
            if ($actual && $actual->getId()) {
                // @fixme this is throwing a 405
                $this->saiku->deleteUser($user);
            }
        }
        $this->assertNotEmpty($actual->getId());
        $this->assertEquals($user->getUsername(), $actual->getUsername());
        $this->assertEquals($user->getEmail(), $actual->getEmail());
    }

    public function testUpdateUser()
    {
        $user = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertInstanceOf(SaikuUser::class, $user);
        $oldEmail = $user->getEmail();
        $oldPassword = $user->getPassword();
        $this->assertNotEquals('another@example.com', $oldEmail);
        $user->setEmail('another@example.com');

        try {
            $actual = $this->saiku->updateUser($user);
        } finally {
            // if the following fails subsequent runs will also fail until you restart docker
            $user->setEmail($oldEmail);
            $this->saiku->updateUser($user);
        }

        $this->assertEquals(self::ADMIN_ID, $actual->getId());
        $this->assertEquals('another@example.com', $actual->getEmail());

        // check password has not been altered
        $actual = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertEquals($oldPassword, $actual->getPassword());
    }

    public function testUpdateNoIdThrowsException()
    {
        $this->expectException(UserException::class);
        $user = new SaikuUser();
        $user->setUsername('foo@test')
            ->setPassword('foo');
        $this->saiku->updateUser($user);
    }

    public function testUpdateNonexistentUserThrowsException()
    {
        $this->expectException(UserException::class);
        $user = new SaikuUser();
        $user->setId(self::INVALID_USER_ID)
            ->setUsername('foo@test')
            ->setPassword('foo');
        $this->saiku->updateUser($user);
    }

    public function testUpdateUserAndPasswordUpdatesPassword()
    {
        $user = $this->saiku->getUser(self::USER_ID);
        $this->assertInstanceOf(SaikuUser::class, $user);
        $oldPassword = $user->getPassword();
        $user->setPassword('foo');

        $actual = $this->saiku->updateUserAndPassword($user);
        $this->assertEquals(self::USER_ID, $actual->getId());
        $this->assertStringStartsWith('$2a$', $actual->getPassword());
        $this->assertNotEquals($oldPassword, $actual->getPassword());

    }

    public function testGetLicenseReturnsLicense()
    {
        $actual = $this->saiku->getLicense();
        $this->assertInstanceOf(SaikuLicense::class, $actual);
    }

    public function testSetLicense()
    {
        $fh = fopen($this->getLicenseFile(), 'r');
        $stream = new Stream($fh);
        $this->saiku->setLicense($stream);

        $actual = $this->saiku->getLicense();
        $this->assertInstanceOf(SaikuLicense::class, $actual);
    }

    private function getInvalidSessionCookie(): SetCookie
    {
        $cookie = new SetCookie();
        $cookie->setName('JSESSIONID');
        $cookie->setValue('12345678901234567890123456789012');
        $cookie->setDomain($GLOBALS['SAIKU_URL']);
        return $cookie;
    }

    private function loadRepository()
    {
        $this->checkLicense();

        $fh = fopen('zip://' . __DIR__ . '/repo.zip#backup.xml', 'r');
        $stream = new Stream($fh);
        try {
            $this->saiku->restore($stream);
            $this->saiku->logout();
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error restoring repository: %s", $e->getMessage()));
        } finally {
            fclose($fh);
        }
    }

    private function checkLicense()
    {
        try {
            $this->saiku->getLicense();
        } catch (LicenseException $e) {
            $this->loadLicense();
        } catch (SaikuException $e) {
            $this->markTestSkipped(sprintf("Error checking license: %s", $e->getMessage()));
        }
    }

    private function loadLicense()
    {
        $file = $this->getLicenseFile();
        $fh = fopen($file, 'r');
        if (! $fh) {
            $this->markTestSkipped(sprintf("Couldn't open '%s' for reading", $file));
        }
        $stream = new Stream($fh);
        try {
            $this->saiku->setLicense($stream);
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error loading license from '%s: %s", $file, $e->getMessage()));
        } finally {
            fclose($fh);
        }
    }

    private function getLicenseFile()
    {
        return __DIR__ . '/../license.lic';
    }
}
