<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;

final class SaikuAcl extends AbstractEntity
{
    const TYPE_PUBLIC = 'PUBLIC';
    const TYPE_PRIVATE = 'PRIVATE';
    const TYPE_SECURED = 'SECURED';

    /**
     * @var string
     */
    private $owner;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $roles = [];
    /**
     * @var array
     */
    private $users = [];

    /**
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     *
     * @return SaikuAcl
     */
    public function setOwner(string $owner): SaikuAcl
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return SaikuAcl
     */
    public function setType(string $type): SaikuAcl
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return SaikuAcl
     */
    public function setRoles(array $roles): SaikuAcl
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return array
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param array $users
     *
     * @return SaikuAcl
     */
    public function setUsers(array $users): SaikuAcl
    {
        $this->users = $users;
        return $this;
    }
}