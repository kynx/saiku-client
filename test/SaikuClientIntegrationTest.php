<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku;

use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Entity\Backup;
use Kynx\Saiku\Entity\License;
use Kynx\Saiku\Entity\User;
use Kynx\Saiku\Exception\BadLoginException;
use Kynx\Saiku\Exception\SaikuExceptionInterface;
use Kynx\Saiku\Exception\UserException;
use Kynx\Saiku\SaikuClient;
use Kynx\Saiku\SaikuRestore;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * These tests WILL mess with your saiku repository and users. Use against a development instance!
 *
 * @group integration
 * @coversNothing
 */
final class SaikuClientIntegrationTest extends TestCase
{
    use IntegrationTrait;

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

    protected function setUp()
    {
        parent::setUp();

        if (! $this->isConfigured()) {
            $this->markTestSkipped("Saiku not configured");
        }

        $this->dump = $GLOBALS['DUMP_HISTORY'] ?? false;

        $this->saiku = $this->getSaiku();

        $this->loadRepository();
        $this->history = [];
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

    public function testGetUsersReturnsUsers()
    {
        $actual = $this->saiku->getUsers();
        $this->assertCount(2, $actual);
        foreach ($actual as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertNotEmpty($user->getId());
            $this->assertNotEmpty($user->getUsername());
            $this->assertRegExp('|\$2a?\$\d\d\$[./0-9A-Za-z]{53}|', $user->getPassword());
        }
    }

    public function testGetUserReturnsUser()
    {
        $actual = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals(self::ADMIN_ID, $actual->getId());
    }

    public function testGetNonexistentUserReturnsEmpty()
    {
        $actual = $this->saiku->getUser(self::INVALID_USER_ID);
        $this->assertNull($actual);
    }

    public function testCreateUserCreates()
    {
        $user = new User();
        $user->setUsername('foo@test')
            ->setPassword('blahblahblah')
            ->setEmail('foo@example.com');

        $actual = $this->saiku->createUser($user);
        $this->assertNotEmpty($actual->getId());
        $this->assertEquals($user->getUsername(), $actual->getUsername());
        $this->assertEquals($user->getEmail(), $actual->getEmail());
    }

    public function testUpdateUser()
    {
        $user = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertInstanceOf(User::class, $user);
        $oldEmail = $user->getEmail();
        $oldPassword = $user->getPassword();
        $this->assertNotEquals('another@example.com', $oldEmail);
        $user->setEmail('another@example.com');

        $actual = $this->saiku->updateUser($user);
        $this->assertEquals(self::ADMIN_ID, $actual->getId());
        $this->assertEquals('another@example.com', $actual->getEmail());

        // check password has not been altered
        $actual = $this->saiku->getUser(self::ADMIN_ID);
        $this->assertEquals($oldPassword, $actual->getPassword());
    }

    public function testUpdateUserNonexistentUserThrowsException()
    {
        $this->expectException(UserException::class);
        $user = new User();
        $user->setId(self::INVALID_USER_ID)
            ->setUsername('foo@test')
            ->setPassword('foo');
        $this->saiku->updateUser($user);
    }

    public function testUpdateUserAndPasswordUpdatesPassword()
    {
        $user = $this->getUser("smith");
        $this->assertInstanceOf(User::class, $user);
        $oldPassword = $user->getPassword();
        $user->setPassword('foo');

        $actual = $this->saiku->updateUserAndPassword($user);
        $this->assertEquals("smith", $actual->getUsername());
        $this->assertStringStartsWith('$2a$', $actual->getPassword());
        $this->assertNotEquals($oldPassword, $actual->getPassword());
    }

    public function testDeleteUserDeletesUser()
    {
        $user = new User();
        $user->setId(self::USER_ID);
        $this->saiku->deleteUser($user);
        $actual = $this->saiku->getUser(self::USER_ID);
        $this->assertNull($actual);
    }

    public function testDeleteNonExistentThrowsNoWobblies()
    {
        $user = new User();
        $user->setId(self::INVALID_USER_ID);
        $this->saiku->deleteUser($user);
        $actual = $this->saiku->getUser(self::INVALID_USER_ID);
        $this->assertNull($actual);
    }

    public function testGetLicenseReturnsLicense()
    {
        $actual = $this->saiku->getLicense();
        $this->assertInstanceOf(License::class, $actual);
    }

    public function testSetLicense()
    {
        $fh = fopen($this->getLicenseFile(), 'r');
        $stream = new Stream($fh);
        $this->saiku->setLicense($stream);

        $actual = $this->saiku->getLicense();
        $this->assertInstanceOf(License::class, $actual);
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
        if (! $this->checkLicense($this->saiku)) {
            $this->markTestSkipped("Error checking license");
        }

        $backup = new Backup(file_get_contents(__DIR__ . '/asset/backup.json'));
        $restore = new SaikuRestore($this->saiku);
        try {
            $restore->restore($backup);
            $this->saiku->logout();
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error restoring repository: %s", $e->getMessage()));
        }
    }

    private function getUser($username): ?User
    {
        return array_reduce($this->saiku->getUsers(), function ($carry, User $user) use ($username) {
            if ($carry instanceof User) {
                return $carry;
            }
            return $user->getUsername() == $username ? $user : null;
        }, null);
    }

    protected function tearDown()
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
}
