<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;

use function array_map;

final class DatasourceResource extends AbstractResource implements DatasourceResourceInterface
{
    public const PATH = 'rest/saiku/admin/datasources/';

    /**
     * {@inheritdoc}
     */
    public function getAll() : array
    {
        try {
            $response = $this->session->request('GET', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return array_map(function (array $item) {
                return new Datasource($item);
            }, $this->decodeResponse($response));
        }

        throw new BadResponseException("Couldn't get datasources", $response);
    }

    /**
     * {@inheritdoc}
     */
    public function create(Datasource $datasource) : Datasource
    {
        $this->validate($datasource);

        $options = [
            'json' => $datasource->toArray(),
        ];

        try {
            $response = $this->session->request('POST', self::PATH, $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return new Datasource((string) $response->getBody());
        }

        throw new BadResponseException("Couldn't create / update datasource", $response);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Datasource $datasource) : Datasource
    {
        $this->validate($datasource);

        if (! $datasource->getId()) {
            throw new EntityException('Datasource must have an id');
        }

        $options = [
            'json' => $datasource->toArray(),
        ];

        try {
            $response = $this->session->request('PUT', self::PATH . $datasource->getId(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return new Datasource((string) $response->getBody());
        }

        throw new BadResponseException("Couldn't create / update datasource", $response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Datasource $datasource) : void
    {
        if (! $datasource->getId()) {
            throw new EntityException('Datasource must have an id');
        }

        try {
            $this->session->request('DELETE', self::PATH . $datasource->getId());
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function validate(Datasource $datasource)
    {
        if (! ($datasource->getAdvanced() || $datasource->getConnectionType())) {
            throw new EntityException('Datasource must contain a connection type or be advanced');
        }
    }
}
