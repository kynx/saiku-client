<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\Acl;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\Acl
 */
class AclTest extends TestCase
{
    /**
     * @var Acl
     */
    private $acl;

    protected function setUp()
    {
        $this->acl = new Acl();
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateNullsEmptyUsers()
    {
        $properties = ['users' => []];
        $acl = new Acl($properties);
        $this->assertNull($acl->getUsers());
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
     * @covers ::setRoles
     * @covers ::getRoles
     */
    public function testSetRoles()
    {
        $this->acl->setRoles(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN'], $this->acl->getRoles());
    }

    /**
     * @covers ::setUsers
     * @covers ::getUsers
     */
    public function testSetUsers()
    {
        $this->acl->setUsers(['slarty']);
        $this->assertEquals(['slarty'], $this->acl->getUsers());
    }
}
