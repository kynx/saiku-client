<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\DatasourceException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\NotFoundException;
use Kynx\Saiku\Client\Exception\ProxyException;
use Kynx\Saiku\Client\Exception\RepositoryException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Exception\SchemaException;
use Kynx\Saiku\Client\Exception\UserException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Client for Saiku's REST API
 *
 * @see https://community.meteorite.bi/docs/
 */
final class SaikuClient
{
    const URL_DATASOURCE = 'rest/saiku/admin/datasources/';
    const URL_INFO = 'rest/saiku/info';
    const URL_LICENSE = 'rest/saiku/api/license/';
    const URL_REPO = 'rest/saiku/api/repository/';
    const URL_REPO_ACL = 'rest/saiku/api/repository/resource/acl/';
    const URL_REPO_RESOURCE = 'rest/saiku/api/repository/resource/';
    const URL_SCHEMA = 'rest/saiku/admin/schema/';
    const URL_SESSION = 'rest/saiku/session';
    const URL_USER = 'rest/saiku/admin/users/';

    private $client;
    private $username;
    private $password;

    public function __construct(ClientInterface $client)
    {
        if (! $client->getConfig('base_uri')) {
            throw new SaikuException("Client must have base_uri configured");
        }
        if (! $client->getConfig('cookies') instanceof CookieJarInterface) {
            throw new SaikuException("Client must have cookies configured");
        }
        $this->client = $client;
    }

    /**
     * Returns new instance with given cookie jar injected
     */
    public function withCookieJar(CookieJarInterface $cookieJar): SaikuClient
    {
        $options = $this->client->getConfig();
        $options['cookies'] = $cookieJar;
        $class = get_class($this->client);
        return new self(new $class($options));
    }

    /**
     * Sets Saiku username to use for connection
     */
    public function setUsername(string $username): SaikuClient
    {
        if ($this->username != $username) {
            $this->getCookieJar()->clear();
        }

        $this->username = $username;
        return $this;
    }

    /**
     * Sets Saiku password to use for connection
     */
    public function setPassword(string $password): SaikuClient
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Logs in to saiku server
     *
     * @throws BadLoginException
     * @throws LicenseException
     * @throws SaikuException
     */
    public function login(): void
    {
        if (! ($this->username && $this->password)) {
            throw new BadLoginException("Username and password must be set");
        }

        try {
            $this->client->request('POST', self::URL_SESSION, [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);
        } catch (GuzzleException $e) {
            if ($this->isUnauthorisedException($e)) {
                $this->throwBadLoginException($e);
            }
            if ($this->isLicenseException($e)) {
                $this->throwLicenseException($e);
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Logs out from saiku server
     *
     * @throws SaikuException
     */
    public function logout(): void
    {
        $this->getCookieJar()->clear();

        try {
            $this->client->request('DELETE', self::URL_SESSION);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns response that results from proxying given request to saiku server
     *
     * @throws ProxyException
     */
    public function proxy(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $options = [];

        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        if ($method == 'GET') {
            $options['query'] = $request->getQueryParams();
        } elseif (in_array($method, ['PATCH', 'POST', 'PUT'])) {
            $options['body'] = $request->getBody();
        }

        try {
            return $this->lazyRequest($request->getMethod(), $path, $options);
        } catch (GuzzleException $e) {
            throw new ProxyException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns array of users
     *
     * @return User[]
     */
    public function getUsers(): array
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_USER);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return array_map(function (array $properties) {
                return new User($properties);
            }, $this->decodeResponse($response));
        } else {
            throw new BadResponseException(sprintf("Error getting users"), $response);
        }
    }

    /**
     * Returns user with given id, if they exist
     *
     * @throws BadLoginException
     * @throws SaikuException
     */
    public function getUser(int $id): ?User
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_USER . $id);
        } catch (ServerException $e) {
            // @fixme Report upstream
            // saiku throws a 500 error when user does not exist :(
            if ($e->getCode() == '500') {
                return null;
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new User((string) $response->getBody());
        } else {
            throw new BadResponseException(sprintf("Error getting user id '%s':", $id), $response);
        }
    }

    /**
     * Creates and returns new user
     *
     * @throws UserException
     */
    public function createUser(User $user): User
    {
        $data = $user->toArray();
        unset($data['id']);

        try {
            $response = $this->lazyRequest('POST', self::URL_USER, ['json' => $data]);
        } catch (GuzzleException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
        return new User((string) $response->getBody());
    }

    /**
     * Updates user without updating password, returning updated user
     */
    public function updateUser(User $user): User
    {
        $user = clone $user;
        $user->setPassword('');

        return $this->updateUserAndPassword($user);
    }

    /**
     * Updates both user and password, returning updated user
     *
     * @throws UserException
     * @throws SaikuException
     */
    public function updateUserAndPassword(User $user): User
    {
        if (! $user->getId()) {
            throw new UserException("Can not update: user has no ID");
        }

        $data = $user->toArray();
        try {
            $response = $this->lazyRequest('PUT', self::URL_USER . $user->getUsername(), ['json' => $data]);
        } catch (ServerException $e) {
            // @todo Report upstream
            // Saiku has probably thrown a NullPointerException because the user doesn't exist. Would be nice
            // if it were a bit more specific
            throw new UserException("Error updating user. Are you sure they exist?");
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new User((string) $response->getBody());
    }

    /**
     * Deletes user
     */
    public function deleteUser(User $user): void
    {
        try {
            // @todo Report upstream
            // The API docs state that we should be passing the username here. They're wrong: we need to pass the ID
            $this->lazyRequest('DELETE', self::URL_USER . $user->getId());
        } catch (ServerException $e) {
            // @todo Report upstream
            // saiku throws 500 error when user does not exist :(
            if ($e->getCode() == 500) {
                return;
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns folder containing entire repository
     *
     * @todo Report upstream
     * Although the endpoint accepts a "path" parameter, using it always returns an empty response. From what I can tell
     * this is a bug in org.saiku.repository.JackRabbitRepositoryManager#getRepoObjects that stops returning repository
     * objects for a folder: folders are not processed because a conditional checks on whether it's a file first.
     *
     * @param bool $contents    If true, node contents are fetched as well
     * @param array|null $types
     *
     * @return Folder
     */
    public function getRespository(bool $contents = false, ?array $types = null): Folder
    {
        try {
            if ($types === null) {
                $types = File::getAllFiletypes();
            }
            $query = [
                'type' => join(',', $types),
            ];
            $response = $this->lazyRequest('GET', self::URL_REPO, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            $folder = new Folder(['repoObjects' => $this->decodeResponse($response)]);
            if ($contents) {
                $this->populateFolderContents($folder);
            }
            return $folder;
        }

        throw new RepositoryException(sprintf(
            "Couldn't get repository: %s",
            (string) $response->getBody()
        ), $response->getStatusCode());
    }

    public function getResource(string $path): string
    {
        try {
            $query = ['file' => $path];
            $response = $this->lazyRequest('GET', self::URL_REPO_RESOURCE, ['query' => $query]);
        } catch (ServerException $e) {
            // @todo Report upstream
            // Saiku throws a 500 error when the resource does not exist :(
            if (strstr((string) $e->getResponse()->getBody(), 'java.util.NoSuchElementException')) {
                throw new NotFoundException(sprintf("Resource '%s' does not exist", $path), 404, $e);
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            return (string) $response->getBody();
        }

        throw new RepositoryException(sprintf(
            "Couldn't get resource at path '%s': %s",
            $path,
            (string) $response->getBody()
        ), $response->getStatusCode());
    }

    public function storeResource(AbstractNode $resource): void
    {
        $params = ['file' => $resource->getPath()];
        if ($resource instanceof File) {
            $params['content'] = $resource->getContent();
        }

        try {
            $this->lazyRequest('POST', self::URL_REPO_RESOURCE, ['form_params' => $params]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteResource(AbstractNode $resource): void
    {
        $query = ['file' => $resource->getPath()];
        try {
            $this->lazyRequest('DELETE', self::URL_REPO_RESOURCE, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns ACL for node at `$path`, or `null` if none set
     */
    public function getAcl(string $path): ?Acl
    {
        $query = ['file' => $path];
        try {
            $response = $this->lazyRequest('GET', self::URL_REPO_ACL, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            $item = $this->decodeResponse($response);
            if (isset($item['type'])) {
                return new Acl($item);
            }
            return null;
        }
        throw new BadResponseException(sprintf("Couldn't get ACL at path '%s'", $path), $response);
    }

    public function setAcl(string $path, Acl $acl): void
    {
        $params = [
            'file' => $path,
            'acl' => json_encode($acl->toArray()),
        ];

        try {
            $this->lazyRequest('POST', self::URL_REPO_ACL, ['form_params' => $params]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return Datasource[]
     */
    public function getDatasources(): array
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_DATASOURCE);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            return array_map(function(array $item) {
                return new Datasource($item);
            }, $this->decodeResponse($response));
        } else {
            throw new BadResponseException("Couldn't get datasources", $response);
        }
    }

    public function createDatasource(Datasource $datasource): Datasource
    {
        $options = [
            'json' => $datasource->toArray(),
        ];

        try {
            $response = $this->lazyRequest('POST', self::URL_DATASOURCE, $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new Datasource((string) $response->getBody());
        }

        throw new BadResponseException("Couldn't create / update datasource", $response);
    }

    public function updateDatasource(Datasource $datasource): Datasource
    {
        if (! $datasource->getId()) {
            throw new DatasourceException("Datasource must have an id");
        }

        $options = [
            'json' => $datasource->toArray(),
        ];

        try {
            $response = $this->lazyRequest('PUT', self::URL_DATASOURCE. $datasource->getId(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new Datasource((string) $response->getBody());
        }

        throw new BadResponseException("Couldn't create / update datasource", $response);
    }

    public function deleteDatasource(Datasource $datasource): void
    {
        if (! $datasource->getId()) {
            throw new DatasourceException("Datasource must have an id");
        }

        try {
            $this->lazyRequest('DELETE', self::URL_DATASOURCE. $datasource->getId());
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(bool $contents = false): array
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_SCHEMA);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            $schemas = array_map(function (array $item) {
                return new Schema($item);
            }, $this->decodeResponse($response));
        } else {
            throw new BadResponseException("Couldn't get datasources", $response);
        }

        if (! $contents) {
            return $schemas;
        }

        return array_map(function (Schema $schema) {
            $schema->setXml($this->getResource($schema->getPath()));
            return $schema;
        }, $schemas);
    }

    public function createSchema(Schema $schema): Schema
    {
        $this->validateSchema($schema);

        $options = [
            'multipart' => [
                [
                    'name' => 'name',
                    'contents' => $schema->getName(),
                ],
                [
                    'name' => 'file',
                    'contents' => $schema->getXml()
                ],
            ],
        ];

        try {
            // @todo Report upstream
            // The api docs indicate that we should be passing an id, but schemas do not have one. From the source it's
            // clear the name is expected.
            $response = $this->lazyRequest('POST', self::URL_SCHEMA . $schema->getName(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed to create schema '%s'", $schema->getName()), $response);
    }

    public function updateSchema(Schema $schema): Schema
    {
        $this->validateSchema($schema);

        $options = [
            'multipart' => [
                [
                    'name' => 'name',
                    'contents' => $schema->getName(),
                ],
                [
                    'name' => 'file',
                    'contents' => $schema->getXml()
                ],
            ],
        ];

        try {
            $response = $this->lazyRequest('PUT', self::URL_SCHEMA . $schema->getName(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed updating schema '%s'", $schema->getName()), $response);
    }

    public function deleteSchema(Schema $schema): void
    {
        $this->validateSchema($schema);

        try {
            // @todo Report upstream
            // The api docs indicate that we should be passing an id, but schemas do not have one. From the source it's
            // clear the name is expected.
            $this->lazyRequest('DELETE', self::URL_SCHEMA . $schema->getName());
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getLicense(): License
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_LICENSE);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new License((string) $response->getBody());
    }

    public function setLicense(StreamInterface $stream): void
    {
        $options = [
            'auth' => [$this->username, $this->password],
            'body' => $stream,
        ];
        try {
            $this->client->request('POST', self::URL_LICENSE, $options);
        } catch (GuzzleException $e) {
            if ($this->isUnauthorisedException($e)) {
                $this->throwBadLoginException($e);
            }
            throw new LicenseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns response from saiku server
     *
     * If no session is active, logs in. If request fails with unauthorised error, logs in and retries request.
     *
     * @throws BadLoginException
     * @throws GuzzleException
     */
    private function lazyRequest(string $method, string $url, array $options = [], int $count = 0): ResponseInterface
    {
        if (empty($this->getCookieJar()->toArray())) {
            $this->login();
        }

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (ClientException $e) {
            // if our session has expired, try request again...
            if ($this->isUnauthorisedException($e)) {
                if ($count) {
                    $this->throwBadLoginException($e);
                }

                $this->login();
                $response = $this->lazyRequest($method, $url, $options, $count + 1);
            } else {
                throw $e;
            }
        }

        return $response;
    }

    private function validateSchema(Schema $schema)
    {
        if (! $schema->getName()) {
            throw new SchemaException("Schema must have a name");
        }
        if (pathinfo($schema->getName(), PATHINFO_EXTENSION)) {
            throw new SchemaException(sprintf(
                "Schema names should not include an extension; '%s' found",
                pathinfo($schema->getName(), PATHINFO_EXTENSION)
            ));
        }
    }

    private function populateFolderContents(Folder $folder): void
    {
        $contentType = [File::FILETYPE_LICENSE, File::FILETYPE_REPORT];
        foreach ($folder->getRepoObjects() as $object) {
            if ($object instanceof Folder) {
                $this->populateFolderContents($object);
            } elseif ($object instanceof File && in_array($object->getFileType(), $contentType)) {
                $object->setContent($this->getResource($object->getPath()));
            }
        }
    }

    private function getCookieJar(): CookieJarInterface
    {
        return $this->client->getConfig('cookies');
    }

    private function decodeResponse(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }

    private function isUnauthorisedException(GuzzleException $exception): bool
    {
        if ($exception instanceof ServerException) {
            // invalid login returns a 500 status :|
            return $exception->getResponse()->getStatusCode() == 500
                && stristr($exception->getMessage(), 'authentication failed');
        } elseif ($exception instanceof ClientException) {
            return $exception->getResponse()->getStatusCode() == 401;
        }
        return false;
    }

    private function isLicenseException(GuzzleException $exception): bool
    {
        if ($exception instanceof ServerException && $exception->getCode() == 500) {
            $body = (string) $exception->getResponse()->getBody();
            return (bool) stristr($body, 'error fetching license');
        }
        return false;
    }

    private function throwBadLoginException(GuzzleException $exception): void
    {
        throw new BadLoginException(sprintf("Couldn't get session for '%s'", $this->username), 401, $exception);
    }

    private function throwLicenseException(GuzzleException $exception): void
    {
        throw new LicenseException("Invalid license", $exception->getCode(), $exception);
    }
}
