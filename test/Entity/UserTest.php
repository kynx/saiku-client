<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\User
 */
class UserTest extends TestCase
{
    /** @var User */
    private $user;

    protected function setUp()
    {
        $this->user = new User();
    }

    /**
     * @covers ::setId
     * @covers ::getId
     */
    public function testSetId()
    {
        $this->user->setId(42);
        $this->assertEquals(42, $this->user->getId());
    }

    /**
     * @covers ::setUsername
     * @covers ::getUsername
     */
    public function testSetUsername()
    {
        $this->user->setUsername('slarty');
        $this->assertEquals('slarty', $this->user->getUsername());
    }

    /**
     * @covers ::setPassword
     * @covers ::getPassword
     */
    public function testSetPassword()
    {
        $this->user->setPassword('abc123');
        $this->assertEquals('abc123', $this->user->getPassword());
    }

    /**
     * @covers ::setEmail
     * @covers ::getEmail
     */
    public function testSetEmail()
    {
        $this->user->setEmail('slarty@fjords.no');
        $this->assertEquals('slarty@fjords.no', $this->user->getEmail());
    }

    /**
     * @covers ::setRoles
     * @covers ::getRoles
     */
    public function testSetRoles()
    {
        $this->user->setRoles(['ROLE_USER']);
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());
    }

    /**
     * @covers ::hasRole
     */
    public function testHasRole()
    {
        $this->user->setRoles(['ROLE_USER']);
        $this->assertTrue($this->user->hasRole('ROLE_USER'));
    }

    /**
     * @covers ::hasRole
     */
    public function testHasRoleReturnsFalse()
    {
        $this->user->setRoles(['ROLE_USER']);
        $this->assertFalse($this->user->hasRole('ROLE_ADMIN'));
    }
}
