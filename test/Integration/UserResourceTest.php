<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\UserResource;

/**
 * @group integration
 * @coversNothing
 */
final class UserResourceTest extends AbstractIntegrationTest
{
    const ADMIN_ID = 1;
    const INVALID_USER_ID = 99999999;

    /**
     * @var UserResource
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();
        $this->user = new UserResource($this->session);
    }

    public function testGetAllReturnsUsers()
    {
        $actual = $this->user->getAll();
        $this->assertCount(2, $actual);
        foreach ($actual as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertNotEmpty($user->getId());
            $this->assertNotEmpty($user->getUsername());
            $this->assertRegExp('|\$2a?\$\d\d\$[./0-9A-Za-z]{53}|', $user->getPassword());
        }
    }

    public function testGetReturnsUser()
    {
        $actual = $this->user->get(self::ADMIN_ID);
        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals(self::ADMIN_ID, $actual->getId());
    }

    public function testGetNonexistentUserReturnsEmpty()
    {
        $actual = $this->user->get(self::INVALID_USER_ID);
        $this->assertNull($actual);
    }

    public function testCreate()
    {
        $user = new User();
        $user->setUsername('foo@test')
            ->setPassword('blahblahblah')
            ->setEmail('foo@example.com');

        $actual = $this->user->create($user);
        $this->assertNotEmpty($actual->getId());
        $this->assertEquals($user->getUsername(), $actual->getUsername());
        $this->assertEquals($user->getEmail(), $actual->getEmail());
    }

    public function testUpdate()
    {
        $user = $this->user->get(self::ADMIN_ID);
        $this->assertInstanceOf(User::class, $user);
        $oldEmail = $user->getEmail();
        $oldPassword = $user->getPassword();
        $this->assertNotEquals('another@example.com', $oldEmail);
        $user->setEmail('another@example.com');

        $actual = $this->user->update($user);
        $this->assertEquals(self::ADMIN_ID, $actual->getId());
        $this->assertEquals('another@example.com', $actual->getEmail());

        // check password has not been altered
        $actual = $this->user->get(self::ADMIN_ID);
        $this->assertEquals($oldPassword, $actual->getPassword());
    }

    public function testUpdateNonexistentUserThrowsException()
    {
        $this->expectException(SaikuException::class);
        $user = new User();
        $user->setId(self::INVALID_USER_ID)
            ->setUsername('foo@test')
            ->setPassword('foo');
        $this->user->update($user);
    }

    public function testUpdateWithPassword()
    {
        $user = $this->getUser("smith");
        $this->assertInstanceOf(User::class, $user);
        $oldPassword = $user->getPassword();
        $user->setPassword('foo');

        $actual = $this->user->updateWithPassword($user);
        $this->assertEquals("smith", $actual->getUsername());
        $this->assertStringStartsWith('$2a$', $actual->getPassword());
        $this->assertNotEquals($oldPassword, $actual->getPassword());
    }

    public function testDelete()
    {
        $user = $this->getUser("smith");
        $this->assertInstanceOf(User::class, $user);
        $this->user->delete($user);
        $actual = $this->getUser("smith");
        $this->assertNull($actual);
    }

    public function testDeleteNonExistentThrowsNoWobblies()
    {
        $user = new User();
        $user->setId(self::INVALID_USER_ID);
        $this->user->delete($user);
        $this->assertTrue(true);
    }

    private function getUser(string $username): ?User
    {
        return array_reduce($this->user->getAll(), function ($carry, User $user) use ($username) {
            return $user->getUsername() == $username ? $user : $carry;
        }, null);
    }
}
