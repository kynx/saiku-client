<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Backup\Entity\Backup;
use Kynx\Saiku\Backup\SaikuRestore;
use Kynx\Saiku\Client\Entity\AbstractEntity;
use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\NotFoundException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Exception\SaikuExceptionInterface;
use Kynx\Saiku\Client\Exception\UserException;
use Kynx\Saiku\Client\SaikuClient;
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
        $user = $this->getUser("smith");
        $this->assertInstanceOf(User::class, $user);
        $this->saiku->deleteUser($user);
        $actual = $this->getUser("smith");
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

    public function testGetRepositoryReturnsFolder()
    {
        $repo = $this->saiku->getRespository();
        $actual = array_map(function (AbstractNode $node) {
            return $node->getName();
        }, $repo->getRepoObjects());
        $expected = ['datasources', 'etc', 'homes'];
        $this->assertEquals($expected, $actual);
    }

    public function testGetRepositoryReturnsContent()
    {
        $repo = $this->saiku->getRespository(true);
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $file = '/homes/home:admin/sample_reports/average_mag_and_depth_over_time.saiku';
        $this->assertArrayHasKey($file, $flattened);
        $actual = $flattened[$file];
        /* @var File $actual */
        $this->assertNotEmpty($actual->getContent());
    }

    public function testGetRepositoryFiltersTypes()
    {
        $repo = $this->saiku->getRespository(false, [File::FILETYPE_SCHEMA]);
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $file = '/homes/home:admin/sample_reports/average_mag_and_depth_over_time.saiku';
        $this->assertArrayNotHasKey($file, $flattened);
        $file = '/datasources/foodmart4.xml';
        $this->assertArrayHasKey($file, $flattened);
    }

    public function testGetResourceReturnsContent()
    {
        $resource = $this->saiku->getResource('/homes/home:admin/sample_reports/average_mag_and_depth_over_time.saiku');
        $this->assertIsString($resource);
        $actual = json_decode($resource, true);
        $this->assertIsArray($actual);
    }

    public function testGetNonExistentResourceThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);
        $this->saiku->getResource('/homes/home:admin/nothere.saiku');
    }

    public function testStoreResourceStoresFile()
    {
        $file = new File();
        $file->setFileType($file::FILETYPE_REPORT);
        $file->setPath('/homes/home:smith/foo.saiku');
        $file->setName('foo.saiku');
        $file->setAcl(['ROLE_USER']);
        $file->setContent('{"foo":"bar"}');

        $this->saiku->storeResource($file);
        $actual = $this->saiku->getResource('/homes/home:smith/foo.saiku');
        $this->assertEquals('{"foo":"bar"}', $actual);
    }

    public function testStoreResourceStoresFolder()
    {
        $folder = new Folder();
        $folder->setPath('/homes/home:smith/foo');
        $folder->setName('foo');
        $folder->setAcl(['ROLE_USER']);

        $this->saiku->storeResource($folder);
        $repo = $this->saiku->getRespository();
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayHasKey('/homes/home:smith/foo', $flattened);
    }

    public function testDeleteResourceDeletes()
    {
        $path = '/homes/home:admin/sample_reports/average_mag_and_depth_over_time.saiku';
        $file = new File();
        $file->setPath($path);

        $this->saiku->deleteResource($file);
        $repo = $this->saiku->getRespository();
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayNotHasKey($path, $flattened);
    }

    public function testDeleteNonExistentResourceDoesNotThrowWobblies()
    {
        $path = '/homes/home:admin/nothere.saiku';
        $file = new File();
        $file->setPath($path);
        $this->saiku->deleteResource($file);
        $this->assertTrue(true);
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

    private function flattenRepo(Folder $folder)
    {
        foreach ($folder->getRepoObjects() as $object) {
            yield $object->getPath() => $object;

            if ($object instanceof Folder) {
                foreach ($this->flattenRepo($object) as $path => $child) {
                    yield $path => $child;
                }
            }
        }
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

    private function getSaiku()
    {
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
        $saiku = new SaikuClient($client);
        $saiku->setUsername($GLOBALS['SAIKU_USERNAME'])
            ->setPassword($GLOBALS['SAIKU_PASSWORD']);

        return $saiku;
    }

    private function isConfigured()
    {
        return isset($GLOBALS['SAIKU_URL']) && isset($GLOBALS['SAIKU_USERNAME']) && isset($GLOBALS['SAIKU_PASSWORD']);
    }

    private function checkLicense(SaikuClient $client): bool
    {
        try {
            $client->getLicense();
        } catch (LicenseException $e) {
            $this->loadLicense($client);
        } catch (SaikuException $e) {
            return false;
        }
        return true;
    }

    private function loadLicense(SaikuClient $client): void
    {
        $file = $this->getLicenseFile();
        $fh = fopen($file, 'r');
        if (! $fh) {
            $this->markTestSkipped(sprintf("Couldn't open '%s' for reading", $file));
        }
        $stream = new Stream($fh);
        try {
            $client->setLicense($stream);
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
