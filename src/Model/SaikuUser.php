<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Model;

final class SaikuUser
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER = 'ROLE_USER';

    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string[]
     */
    private $roles = [];

    public function __construct(?string $json = null)
    {
        if ($json) {
            $properties = json_decode($json, true);
            $vars = array_keys(get_class_vars(self::class));
            foreach ($vars as $var) {
                if (isset($properties[$var])) {
                    $this->$var = $properties[$var];
                }
            }
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return SaikuUser
     */
    public function setId(?int $id): SaikuUser
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return SaikuUser
     */
    public function setUsername(string $username): SaikuUser
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return SaikuUser
     */
    public function setPassword(string $password): SaikuUser
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return SaikuUser
     */
    public function setEmail(string $email): SaikuUser
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string[] $roles
     *
     * @return SaikuUser
     */
    public function setRoles(array $roles): SaikuUser
    {
        $this->roles = $roles;
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }
}
