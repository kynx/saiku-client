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
     * @covers ::setOwner
     * @covers ::getOwner
     */
    public function testSetOwner()
    {
        $this->acl->setOwner('slarty');
        $this->assertEquals('slarty', $this->acl->getOwner());
    }

    /**
     * @covers ::setType
     * @covers ::getType
     */
    public function testSetType()
    {
        $this->acl->setType(Acl::TYPE_PRIVATE);
        $this->assertEquals(Acl::TYPE_PRIVATE, $this->acl->getType());
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
     * @covers ::addRole
     * @covers ::getRoles
     */
    public function tesAddRole()
    {
        $this->acl->addRole('ROLE_ADMIN', [Acl::METHOD_NONE]);
        $this->assertEquals(['ROLE_ADMIN' => [Acl::METHOD_NONE]], $this->acl->getRoles());
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
     * @covers ::addUser
     * @covers ::getUsers
     */
    public function testAddtUser()
    {
        $this->acl->addUser('slarty', [Acl::METHOD_NONE]);
        $this->assertEquals(['slarty' => [Acl::METHOD_NONE]], $this->acl->getUsers());
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
