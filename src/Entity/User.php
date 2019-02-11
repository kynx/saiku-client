<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

use function in_array;

final class User extends AbstractEntity
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER  = 'ROLE_USER';

    /** @var int */
    protected $id;
    /** @var string */
    protected $username;
    /** @var string */
    protected $password;
    /** @var string */
    protected $email;
    /** @var string[] */
    protected $roles = [];

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setId(?int $id) : User
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername() : string
    {
        return $this->username;
    }

    public function setUsername(string $username) : User
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword() : string
    {
        return $this->password;
    }

    public function setPassword(string $password) : User
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function setEmail(string $email) : User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles) : User
    {
        $this->roles = $roles;
        return $this;
    }

    public function hasRole(string $role) : bool
    {
        return in_array($role, $this->roles);
    }
}
