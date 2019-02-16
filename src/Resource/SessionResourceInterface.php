<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Psr\Http\Message\ResponseInterface;

interface SessionResourceInterface
{
    /**
     * Returns username for session
     */
    public function getUsername() : ?string;

    /**
     * Sets username for session
     */
    public function setUsername(string $username) : void;

    /**
     * Returns password for session
     */
    public function getPassword() : ?string;

    /**
     * Sets password for session
     */
    public function setPassword(string $password) : void;

    /**
     * Logs in to saiku server
     */
    public function get() : void;

    /**
     * Logs out from saiku server
     */
    public function clear() : void;

    /**
     * Returns response from saiku server
     *
     * If no session is active, logs in. If request fails with unauthorised error, logs in and retries request.
     */
    public function request(string $method, string $url, array $options = [], int $count = 0) : ResponseInterface;
}
