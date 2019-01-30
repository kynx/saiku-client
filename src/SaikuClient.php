<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Kynx\Saiku\Exception\BadLoginException;
use Kynx\Saiku\Exception\BadResponseException;
use Kynx\Saiku\Exception\LicenseException;
use Kynx\Saiku\Exception\ProxyException;
use Kynx\Saiku\Exception\RepositoryException;
use Kynx\Saiku\Exception\UserException;
use Kynx\Saiku\Exception\SaikuException;
use Kynx\Saiku\Model\AbstractObject;
use Kynx\Saiku\Model\SaikuAcl;
use Kynx\Saiku\Model\SaikuFile;
use Kynx\Saiku\Model\SaikuFolder;
use Kynx\Saiku\Model\SaikuLicense;
use Kynx\Saiku\Model\SaikuUser;
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
    const URL_BACKUP = 'rest/saiku/api/repository/zip/';
    const URL_INFO = 'rest/saiku/info';
    const URL_LICENSE = 'rest/saiku/api/license/';
    const URL_REPO = 'rest/saiku/api/repository/';
    const URL_RESTORE = 'rest/saiku/api/repository/zipupload/';
    const URL_SESSION = 'rest/saiku/session/';
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
     * @return SaikuUser[]
     */
    public function getUsers(): array
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_USER);
            if ($response->getStatusCode() == '200') {
                return array_map(function ($properties) {
                    return new SaikuUser($properties);
                }, json_decode($response->getBody(), true));
            } else {
                throw new BadResponseException(sprintf("Error getting users"), $response);
            }
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns user with given id, if they exist
     *
     * @throws BadLoginException
     * @throws SaikuException
     */
    public function getUser(int $id): ?SaikuUser
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_USER . $id);
            if ($response->getStatusCode() == '200') {
                return new SaikuUser((string) $response->getBody());
            } else {
                throw new BadResponseException(sprintf("Error getting user id '%s':", $id), $response);
            }
        } catch (ServerException $e) {
            // saiku throws a 500 error when user does not exist :(
            if ($e->getCode() == '500') {
                return null;
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates and returns new user
     *
     * @throws UserException
     */
    public function createUser(SaikuUser $user): SaikuUser
    {
        $data = $user->toArray();
        unset($data['id']);

        try {
            $response = $this->lazyRequest('POST', self::URL_USER, ['json' => $data]);
        } catch (GuzzleException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
        return new SaikuUser((string) $response->getBody());
    }

    /**
     * Updates user without updating password, returning updated user
     */
    public function updateUser(SaikuUser $user): SaikuUser
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
    public function updateUserAndPassword(SaikuUser $user): SaikuUser
    {
        if (! $user->getId()) {
            throw new UserException("Can not update: user has no ID");
        }

        $data = $user->toArray();
        try {
            $response = $this->lazyRequest('PUT', self::URL_USER . $user->getUsername(), ['json' => $data]);
        } catch (ServerException $e) {
            // Saiku has probably thrown a NullPointerException because the user doesn't exist. Would be nice
            // if it were a bit more specific
            throw new UserException("Error updating user. Are you sure they exist?");
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new SaikuUser((string) $response->getBody());
    }

    /**
     * Deletes user
     */
    public function deleteUser(SaikuUser $user): void
    {
        try {
            $this->lazyRequest('DELETE', self::URL_USER . $user->getUsername());
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getRepository(?array $types = null): SaikuFolder
    {
        try {
            if ($types === null) {
                $types = SaikuFile::getAllFiletypes();
            }
            $query = ['type' => join(',', $types)];
            $response = $this->lazyRequest('GET', self::URL_REPO, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            return new SaikuFolder((string) $response->getBody());
        }
        throw new RepositoryException("Couldn't get repository", $response->getStatusCode());
    }

    public function saveObject(AbstractObject $object): void
    {

    }

    public function getAcl(string $path): SaikuAcl
    {

    }

    public function setAcl(string $path, SaikuAcl $acl): void
    {

    }

    public function getLicense(): SaikuLicense
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_LICENSE);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new SaikuLicense((string) $response->getBody());
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
     * Returns stream containing zip of saiku repository backup
     */
    public function backup(): StreamInterface
    {
        try {
            $response = $this->lazyRequest('GET', self::URL_BACKUP);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
        if ($response->getStatusCode() != 200) {
            throw new BadResponseException("Backup failed", $response);
        }
        return $response->getBody();
    }

    /**
     * Restores saiku repository from backup.
     *
     * Note: Although `backup()` returns a zip, saiku expects an XML file (ie 'backup.xml' from a backup zip) to restore.
     */
    public function restore(StreamInterface $backup): void
    {
        $options = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $backup,
                ],
            ],
        ];

        try {
            $this->lazyRequest('POST', self::URL_RESTORE, $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
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

    private function getCookieJar(): CookieJarInterface
    {
        return $this->client->getConfig('cookies');
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
