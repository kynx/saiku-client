<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Psr\Http\Message\ResponseInterface;

final class SessionResource implements SessionResourceInterface
{
    use ExceptionTrait;

    public const PATH = 'rest/saiku/session';

    private $client;
    private $username;
    private $password;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername() : ?string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername(string $username) : void
    {
        $this->getCookieJar()->clear();
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword() : ?string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword(string $password) : void
    {
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     * @throws BadLoginException
     * @throws LicenseException
     * @throws SaikuException
     */
    public function get() : void
    {
        if (! ($this->username && $this->password)) {
            throw new BadLoginException('Username and password must be set');
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
                $this->throwBadLoginException($e, $this->username);
            }
            if ($this->isLicenseException($e)) {
                $this->throwLicenseException($e);
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws SaikuException
     */
    public function clear() : void
    {
        $this->getCookieJar()->clear();

        try {
            $this->client->request('DELETE', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     * @throws GuzzleException
     */
    public function request(string $method, string $url, array $options = [], int $count = 0) : ResponseInterface
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
                    $this->throwBadLoginException($e, $this->username);
                }

                $this->get();
                $response = $this->request($method, $url, $options, $count + 1);
            } else {
                throw $e;
            }
        }

        return $response;
    }

    private function getCookieJar() : CookieJarInterface
    {
        return $this->client->getConfig('cookies');
    }
}
