<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client;

use GuzzleHttp\Cookie\CookieJarInterface;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\DatasourceResourceInterface;
use Kynx\Saiku\Client\Resource\LicenseResourceInterface;
use Kynx\Saiku\Client\Resource\RepositoryResourceInterface;
use Kynx\Saiku\Client\Resource\SchemaResourceInterface;
use Kynx\Saiku\Client\Resource\UserResourceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Client for Saiku's REST API
 *
 * @see https://community.meteorite.bi/docs/
 */
interface SaikuInterface
{
    /**
     * Returns new instance with given cookie jar injected
     */
    public function withCookieJar(CookieJarInterface $cookieJar) : SaikuInterface;

    /**
     * Sets Saiku username to use for connection
     */
    public function setUsername(string $username) : SaikuInterface;

    /**
     * Sets Saiku password to use for connection
     */
    public function setPassword(string $password) : SaikuInterface;

    /**
     * Logs in to saiku server
     */
    public function login() : void;

    /**
     * Logs out from saiku server
     */
    public function logout() : void;

    /**
     * Returns response that results from proxying given request to saiku server
     *
     * @throws SaikuException
     */
    public function proxy(ServerRequestInterface $request) : ResponseInterface;

    /**
     * Returns datasource resource
     */
    public function datasource() : DatasourceResourceInterface;

    /**
     * Returns license resource
     */
    public function license() : LicenseResourceInterface;

    /**
     * Returns repository resource
     */
    public function repository() : RepositoryResourceInterface;

    /**
     * Returns schema resource
     */
    public function schema() : SchemaResourceInterface;

    /**
     * Returns user resource
     */
    public function user() : UserResourceInterface;
}
