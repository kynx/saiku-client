<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\GuzzleException;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;

final class SchemaResource extends AbstractResource
{
    const PATH = 'rest/saiku/admin/schema/';

    /**
     * @return Schema[]
     */
    public function getAll(): array
    {
        try {
            $response = $this->session->request('GET', self::PATH);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == 200) {
            return array_map(function (array $item) {
                return new Schema($item);
            }, $this->decodeResponse($response));
        } else {
            throw new BadResponseException("Couldn't get datasources", $response);
        }
    }

    public function create(Schema $schema): Schema
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
                    'name' => 'file',
                    'contents' => $schema->getXml()
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

        if ($response->getStatusCode() == '200') {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed to create schema '%s'", $schema->getName()), $response);
    }

    public function update(Schema $schema): Schema
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
                    'name' => 'file',
                    'contents' => $schema->getXml()
                ],
            ],
        ];

        try {
            $response = $this->session->request('PUT', self::PATH . $schema->getName(), $options);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() == '200') {
            return new Schema((string) $response->getBody());
        }

        throw new BadResponseException(sprintf("Failed updating schema '%s'", $schema->getName()), $response);
    }

    public function delete(Schema $schema): void
    {
        $this->validate($schema);

        try {
            // @todo Report upstream
            // The api docs indicate that we should be passing an id, but schemas do not have one. From the source it's
            // clear the name is expected.
            $this->session->request('DELETE', self::PATH . $schema->getName());
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function validate(Schema $schema)
    {
        if (! $schema->getName()) {
            throw new EntityException("Schema must have a name");
        }
    }
}
