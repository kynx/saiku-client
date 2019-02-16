<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\SaikuException;

interface UserResourceInterface
{
    /**
     * Returns array of users
     *
     * @return User[]
     */
    public function getAll() : array;

    /**
     * Returns user with given id, if they exist
     *
     * @throws SaikuException
     */
    public function get(int $id) : ?User;

    /**
     * Creates and returns new user
     *
     * @throws SaikuException
     */
    public function create(User $user) : User;

    /**
     * Updates user without updating password, returning updated user
     */
    public function update(User $user) : User;

    /**
     * Updates both user and password, returning updated user
     *
     * @throws SaikuException
     */
    public function updatePassword(User $user) : User;

    /**
     * Deletes user
     */
    public function delete(User $user) : void;
}
