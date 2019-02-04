<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

final class Acl extends AbstractEntity
{
    const TYPE_PUBLIC = 'PUBLIC';
    const TYPE_PRIVATE = 'PRIVATE';
    const TYPE_SECURED = 'SECURED';

    /**
     * @var string
     */
    protected $owner;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var array
     */
    protected $roles = [];
    /**
     * @var array
     */
    protected $users;

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
     * @return Acl
     */
    public function setOwner(string $owner): Acl
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
     * @return Acl
     */
    public function setType(string $type): Acl
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
     * @return Acl
     */
    public function setRoles(array $roles): Acl
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return array
     */
    public function getUsers(): ?array
    {
        return $this->users;
    }

    /**
     * @param array $users
     *
     * @return Acl
     */
    public function setUsers(array $users): Acl
    {
        $this->users = $users;
        return $this;
    }

    protected function hydrate(array $properties): void
    {
        if (empty($properties['users'])) {
            // ERROR [JackRabbitRepositoryManager] Could not read ACL blob
            // com.fasterxml.jackson.databind.JsonMappingException: Can not deserialize instance of java.util.LinkedHashMap out of START_ARRAY token
            // at [Source: {"owner":"admin","type":"SECURED","roles":{"ROLE_USER":["READ"]},"users":[]}; line: 1, column: 65] (through reference chain: org.saiku.repository.AclEntry["users"])
            unset($properties['users']);
        }
        parent::hydrate($properties);
    }
}
