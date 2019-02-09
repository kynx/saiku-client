<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Psr\Http\Message\ResponseInterface;

final class SessionResource
{
    use ExceptionTrait;

    const PATH = 'rest/saiku/session';

    private $client;
    private $username;
    private $password;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username)
    {
        $this->getCookieJar()->clear();
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * Logs in to saiku server
     *
     * @throws BadLoginException
     * @throws LicenseException
     * @throws SaikuException
     */
    public function get(): void
    {
        if (! ($this->username && $this->password)) {
            throw new BadLoginException("Username and password must be set");
        }

        try {
            $this->client->request('POST', self::PATH, [
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
    public function clear(): void
    {
        $this->getCookieJar()->clear();

        try {
            $this->client->request('DELETE', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns response from saiku server
     *
     * If no session is active, logs in. If request fails with unauthorised error, logs in and retries request.
     *
     * @throws GuzzleException
     */
    public function request(string $method, string $url, array $options = [], int $count = 0): ResponseInterface
    {
        if (empty($this->getCookieJar()->toArray())) {
            $this->get();
        }

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (ClientException $e) {
            // if our session has expired, try request again...
            if ($this->isUnauthorisedException($e)) {
                if ($count) {
                    $this->throwBadLoginException($e);
                }

                $this->get();
                $response = $this->request($method, $url, $options, $count + 1);
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
}
