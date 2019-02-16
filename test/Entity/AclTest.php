<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Exception\EntityException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\Acl
 */
class AclTest extends TestCase
{
    /** @var Acl */
    private $acl;

    protected function setUp()
    {
        $this->acl = new Acl();
    }

    /**
     * @covers ::extract
     */
    public function testExtractNullsEmptyUsers()
    {
        $acl       = new Acl();
        $extracted = $acl->toArray();
        $this->assertNull($extracted['users']);
    }

    /**
     * @covers ::extract
     */
    public function testExtractNullsEmptyRoles()
    {
        $acl       = new Acl();
        $extracted = $acl->toArray();
        $this->assertNull($extracted['roles']);
    }

    /**
     * @covers ::getOwner
     */
    public function testGetOwner()
    {
        $acl    = new Acl('{"owner":"slarty"}');
        $actual = $acl->getOwner();
        $this->assertEquals('slarty', $actual);
    }

    /**
     * @covers ::setOwner
     */
    public function testSetOwner()
    {
        $this->acl->setOwner('slarty');
        $actual = $this->acl->getOwner();
        $this->assertEquals('slarty', $actual);
    }

    /**
     * @covers ::getType
     */
    public function testGetType()
    {
        $acl    = new Acl('{"type":"PRIVATE"}');
        $actual = $acl->getType();
        $this->assertEquals('PRIVATE', $actual);
    }

    /**
     * @covers ::setType
     */
    public function testSetType()
    {
        $this->acl->setType(Acl::TYPE_PRIVATE);
        $actual = $this->acl->getType();
        $this->assertEquals(Acl::TYPE_PRIVATE, $actual);
    }

    /**
     * @covers ::setType
     */
    public function testSetTypeInvalidThrowsEntityException()
    {
        $this->expectException(EntityException::class);
        $this->acl->setType('FOO');
    }

    /**
     * @covers ::getRoles
     */
    public function testGetRoles()
    {
        $acl    = new Acl('{"roles":{"ROLE_ADMIN":["NONE"]}}');
        $actual = $acl->getRoles();
        $this->assertEquals(['ROLE_ADMIN' => ['NONE']], $actual);
    }

    /**
     * @covers ::addRole
     */
    public function testAddRole()
    {
        $this->acl->addRole('ROLE_ADMIN', [Acl::METHOD_NONE]);
        $actual = $this->acl->getRoles();
        $this->assertEquals(['ROLE_ADMIN' => [Acl::METHOD_NONE]], $actual);
    }

    /**
     * @covers ::addRole
     */
    public function testAddRoleInvalidMethodThrowsEntityException()
    {
        $this->expectException(EntityException::class);
        $this->acl->addRole('ROLE_ADMIN', ['FOO']);
    }

    /**
     * @covers ::getUsers
     */
    public function testGetUsers()
    {
        $acl    = new Acl('{"users":{"slarty":["NONE"]}}');
        $actual = $acl->getUsers();
        $this->assertEquals(['slarty' => ['NONE']], $actual);
    }

    /**
     * @covers ::addUser
     * @covers ::validateMethods
     */
    public function testAddUser()
    {
        $this->acl->addUser('slarty', [Acl::METHOD_NONE]);
        $actual = $this->acl->getUsers();
        $this->assertEquals(['slarty' => [Acl::METHOD_NONE]], $actual);
    }

    /**
     * @covers ::validateMethods
     */
    public function testAddUserInvalidMethodThrowsEntityException()
    {
        $this->expectException(EntityException::class);
        $this->acl->addUser('slarty', ['FOO']);
    }
}
