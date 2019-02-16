<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;

use function array_map;
use function basename;
use function sprintf;

final class SchemaResource extends AbstractResource implements SchemaResourceInterface
{
    public const PATH = 'rest/saiku/admin/schema/';

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
                return new Schema($item);
            }, $this->decodeResponse($response));
        }

        throw new BadResponseException("Couldn't get datasources", $response);
    }

    /**
     * {@inheritdoc}
     */
    public function create(Schema $schema) : Schema
    {
        $this->validate($schema);

        $options = [
            'multipart' => [
                [
                    'name' => 'name',
                    // saiku appends ".xml" to the name before saving, resulting in duplicates
                    'contents' => basename($schema->getName(), '.xml'),
                ],
                [
                    'name'     => 'file',
                    'contents' => $schema->getXml(),
                ],
            ],
        ];

        try {
            // @todo Report upstream
            // The api docs indicate that we should be passing an id, but schemas do not have one. From the source it's
            // clear the name is expected.
            $response = $this->session->request('POST', self::PATH . $schema->getName(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed to create schema '%s'", $schema->getName()), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Schema $schema) : Schema
    {
        $this->validate($schema);

        $options = [
            'multipart' => [
                [
                    'name' => 'name',
                    // saiku appends ".xml" to the name before saving, resulting in duplicates
                    'contents' => basename($schema->getName(), '.xml'),
                ],
                [
                    'name'     => 'file',
                    'contents' => $schema->getXml(),
                ],
            ],
        ];

        try {
            $response = $this->session->request('PUT', self::PATH . $schema->getName(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed updating schema '%s'", $schema->getName()), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Schema $schema) : void
    {
        $this->validate($schema);
        $headers = [
            // phpcs:disable
            // Here be the secret sauce. Without an accept header saiku barfs with:
            // com.sun.jersey.api.MessageException: A message body writer for Java class java.util.ArrayList, and Java type class java.util.ArrayList, and MIME media type application/octet-stream was not found.
            // phpcs:enable
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
        ];

        try {
            // @todo Report upstream
            // The api docs indicate that we should be passing an id, but schemas do not have one. From the source it's
            // clear the name is expected.
            $this->session->request('DELETE', self::PATH . $schema->getName(), ['headers' => $headers]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function validate(Schema $schema)
    {
        if (! $schema->getName()) {
            throw new EntityException('Schema must have a name');
        }
    }
}
