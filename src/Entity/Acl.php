<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

use Kynx\Saiku\Client\Exception\EntityException;

use function array_diff;
use function count;
use function implode;
use function in_array;
use function is_array;
use function sprintf;

final class Acl extends AbstractEntity
{
    public const TYPE_PUBLIC  = 'PUBLIC';
    public const TYPE_PRIVATE = 'PRIVATE';
    public const TYPE_SECURED = 'SECURED';

    public const METHOD_NONE  = 'NONE';
    public const METHOD_READ  = 'READ';
    public const METHOD_WRITE = 'WRITE';
    public const METHOD_GRANT = 'GRANT';

    /** @var string */
    protected $owner;
    /** @var string */
    protected $type;
    /** @var array */
    protected $roles = [];
    /** @var array */
    protected $users = [];

    public function getOwner() : string
    {
        return $this->owner;
    }

    public function setOwner(string $owner) : Acl
    {
        $this->owner = $owner;
        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : Acl
    {
        $valid = [
            self::TYPE_PUBLIC,
            self::TYPE_PRIVATE,
            self::TYPE_SECURED,
        ];
        if (! in_array($type, $valid)) {
            throw new EntityException(sprintf(
                "Invalid type '%s'. Valid types are %s",
                $type,
                implode(', ', $valid)
            ));
        }
        $this->type = $type;
        return $this;
    }

    public function getRoles() : array
    {
        return $this->roles;
    }

    public function addRole(string $role, array $methods) : Acl
    {
        $this->validateMethods($methods);

        $this->roles[$role] = $methods;
        return $this;
    }

    public function getUsers() : array
    {
        return $this->users;
    }

    public function addUser(string $user, array $methods) : Acl
    {
        $this->validateMethods($methods);

        if (! is_array($this->users)) {
            $this->users = [];
        }
        $this->users[$user] = $methods;
        return $this;
    }

    protected function extract() : array
    {
        // @todo Report upstream
        // The JsonMapping really doesn't like empty hash maps...
        // phpcs:disable
        // ERROR [JackRabbitRepositoryManager] Could not read ACL blob
        // com.fasterxml.jackson.databind.JsonMappingException: Can not deserialize instance of java.util.LinkedHashMap out of START_ARRAY token
        // at [Source: {"owner":"admin","type":"SECURED","roles":{"ROLE_USER":["READ"]},"users":[]}; line: 1, column: 65] (through reference chain: org.saiku.repository.AclEntry["users"])
        //
        // that json is exactly what saiku sends us :(
        // phpcs:enable
        $extracted = parent::extract();
        if (empty($extracted['users'])) {
            $extracted['users'] = null;
        }
        if (empty($extracted['roles'])) {
            $extracted['roles'] = null;
        }
        return $extracted;
    }

    private function validateMethods(array $methods) : void
    {
        $valid   = [
            self::METHOD_NONE,
            self::METHOD_READ,
            self::METHOD_WRITE,
            self::METHOD_GRANT,
        ];
        $invalid = array_diff($methods, $valid);
        if (count($invalid)) {
            throw new EntityException(sprintf(
                'Invalid method(s) %s. Valid methods are %s',
                implode(', ', $methods),
                implode(', ', $valid)
            ));
        }
    }
}
