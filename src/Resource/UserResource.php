<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;

use function array_map;
use function sprintf;

final class UserResource extends AbstractResource
{
    public const PATH = 'rest/saiku/admin/users/';

    /**
     * Returns array of users
     *
     * @return User[]
     */
    public function getAll() : array
    {
        try {
            $response = $this->session->request('GET', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return array_map(function (array $properties) {
                return new User($properties);
            }, $this->decodeResponse($response));
        }

        throw new BadResponseException(sprintf('Error getting users'), $response);
    }

    /**
     * Returns user with given id, if they exist
     *
     * @throws SaikuException
     */
    public function get(int $id) : ?User
    {
        try {
            $response = $this->session->request('GET', self::PATH . $id);
        } catch (ServerException $e) {
            // @fixme Report upstream
            // saiku throws a 500 error when user does not exist :(
            if ($e->getCode() === 500) {
                return null;
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return new User((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Error getting user id '%s':", $id), $response);
    }

    /**
     * Creates and returns new user
     *
     * @throws SaikuException
     */
    public function create(User $user) : User
    {
        $data = $user->toArray();
        unset($data['id']);

        try {
            $response = $this->session->request('POST', self::PATH, ['json' => $data]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
        return new User((string) $response->getBody());
    }

    /**
     * Updates user without updating password, returning updated user
     */
    public function update(User $user) : User
    {
        $user = clone $user;
        $user->setPassword('');

        return $this->updatePassword($user);
    }

    /**
     * Updates both user and password, returning updated user
     *
     * @throws SaikuException
     */
    public function updatePassword(User $user) : User
    {
        if (! $user->getId()) {
            throw new EntityException('Can not update: user has no ID');
        }

        $data = $user->toArray();
        try {
            $response = $this->session->request('PUT', self::PATH . $user->getUsername(), ['json' => $data]);
        } catch (ServerException $e) {
            // @todo Report upstream
            // Saiku has probably thrown a NullPointerException because the user doesn't exist. Would be nice
            // if it were a bit more specific
            throw new SaikuException('Error updating user. Are you sure they exist?', $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new User((string) $response->getBody());
    }

    /**
     * Deletes user
     */
    public function delete(User $user) : void
    {
        try {
            // @todo Report upstream
            // The API docs state that we should be passing the username here. They're wrong: we need to pass the ID
            $this->session->request('DELETE', self::PATH . $user->getId());
        } catch (ServerException $e) {
            // @todo Report upstream
            // saiku throws 500 error when user does not exist :(
            if ($e->getCode() === 500) {
                return;
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
