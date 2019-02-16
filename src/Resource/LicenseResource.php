<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Exception\SaikuException;
use Psr\Http\Message\StreamInterface;

final class LicenseResource implements LicenseResourceInterface
{
    use ExceptionTrait;

    public const PATH = 'rest/saiku/api/license/';

    private $session;
    private $client;

    public function __construct(SessionResource $session, ClientInterface $client)
    {
        $this->session = $session;
        $this->client  = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function get() : License
    {
        try {
            $response = $this->session->request('GET', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new License((string) $response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    public function set(StreamInterface $stream) : void
    {
        $options = [
            'auth'    => [$this->session->getUsername(), $this->session->getPassword()],
            'cookies' => false,
            'body'    => $stream,
        ];
        try {
            $this->client->request('POST', self::PATH, $options);
        } catch (GuzzleException $e) {
            if ($this->isUnauthorisedException($e)) {
                $this->throwBadLoginException($e, $this->session->getUsername());
            }
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
