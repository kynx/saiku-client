<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Entity\License;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Psr\Http\Message\StreamInterface;

final class LicenseResource
{
    use ExceptionTrait;

    const PATH = 'rest/saiku/api/license/';

    private $session;
    private $client;

    public function __construct(SessionResource $session, ClientInterface $client)
    {
        $this->session = $session;
        $this->client = $client;
    }

    public function get(): License
    {
        try {
            $response = $this->session->request('GET', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        return new License((string) $response->getBody());
    }

    public function set(StreamInterface $stream): void
    {
        $options = [
            'auth' => [$this->session->getUsername(), $this->session->getPassword()],
            'body' => $stream,
        ];
        try {
            $this->client->request('POST', self::PATH, $options);
        } catch (GuzzleException $e) {
            if ($this->isUnauthorisedException($e)) {
                $this->throwBadLoginException($e);
            }
            throw new LicenseException($e->getMessage(), $e->getCode(), $e);
        }
    }
}