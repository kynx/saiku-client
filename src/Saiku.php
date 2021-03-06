<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\DatasourceResource;
use Kynx\Saiku\Client\Resource\DatasourceResourceInterface;
use Kynx\Saiku\Client\Resource\LicenseResource;
use Kynx\Saiku\Client\Resource\LicenseResourceInterface;
use Kynx\Saiku\Client\Resource\RepositoryResource;
use Kynx\Saiku\Client\Resource\RepositoryResourceInterface;
use Kynx\Saiku\Client\Resource\SchemaResource;
use Kynx\Saiku\Client\Resource\SchemaResourceInterface;
use Kynx\Saiku\Client\Resource\SessionResource;
use Kynx\Saiku\Client\Resource\UserResource;
use Kynx\Saiku\Client\Resource\UserResourceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_map;
use function get_class;
use function implode;
use function in_array;
use function strpos;
use function substr;

/**
 * Client for Saiku's REST API
 *
 * @see https://community.meteorite.bi/docs/
 */
final class Saiku implements SaikuInterface
{
    private $client;
    private $datasource;
    private $license;
    private $repository;
    private $schema;
    private $session;
    private $user;

    public function __construct(ClientInterface $client)
    {
        if (! $client->getConfig('base_uri')) {
            throw new SaikuException('Client must have base_uri configured');
        }
        if (! $client->getConfig('cookies') instanceof CookieJarInterface) {
            throw new SaikuException('Client must have cookies configured');
        }

        $this->client  = $client;
        $this->session = new SessionResource($this->client);
    }

    /**
     * Returns new instance with given cookie jar injected
     */
    public function withCookieJar(CookieJarInterface $cookieJar) : SaikuInterface
    {
        $options            = $this->client->getConfig();
        $options['cookies'] = $cookieJar;
        $class              = get_class($this->client);
        return new self(new $class($options));
    }

    /**
     * Sets Saiku username to use for connection
     */
    public function setUsername(string $username) : SaikuInterface
    {
        $this->session->setUsername($username);
        return $this;
    }

    /**
     * Sets Saiku password to use for connection
     */
    public function setPassword(string $password) : SaikuInterface
    {
        $this->session->setPassword($password);
        return $this;
    }

    /**
     * Logs in to saiku server
     */
    public function login() : void
    {
        $this->session->get();
    }

    /**
     * Logs out from saiku server
     */
    public function logout() : void
    {
        $this->session->clear();
    }

    /**
     * Returns response that results from proxying given request to saiku server
     * @throws SaikuException
     */
    public function proxy(ServerRequestInterface $request) : ResponseInterface
    {
        $path   = $request->getUri()->getPath();
        $method = $request->getMethod();

        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        $options = [
            'query'   => $request->getUri()->getQuery(),
            'headers' => $this->getProxyHeaders($request),
        ];
        if (in_array($method, ['PATCH', 'POST', 'PUT'])) {
            $options['body'] = $request->getBody();
        }

        try {
            return $this->session->request($request->getMethod(), $path, $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns datasource resource
     */
    public function datasource() : DatasourceResourceInterface
    {
        if (! $this->datasource) {
            $this->datasource = new DatasourceResource($this->session);
        }
        return $this->datasource;
    }

    /**
     * Returns license resource
     */
    public function license() : LicenseResourceInterface
    {
        if (! $this->license) {
            $this->license = new LicenseResource($this->session, $this->client);
        }
        return $this->license;
    }

    /**
     * Returns repository resource
     */
    public function repository() : RepositoryResourceInterface
    {
        if (! $this->repository) {
            $this->repository = new RepositoryResource($this->session);
        }
        return $this->repository;
    }

    /**
     * Returns schema resource
     */
    public function schema() : SchemaResourceInterface
    {
        if (! $this->schema) {
            $this->schema = new SchemaResource($this->session);
        }
        return $this->schema;
    }

    /**
     * Returns user resource
     */
    public function user() : UserResourceInterface
    {
        if (! $this->user) {
            $this->user = new UserResource($this->session);
        }
        return $this->user;
    }

    private function getProxyHeaders(ServerRequestInterface $request) : array
    {
        $headers = $request->getHeaders();
        unset($headers['Cookie']);
        return array_map(function (array $header) {
            return implode(',', $header);
        }, $headers);
    }
}
