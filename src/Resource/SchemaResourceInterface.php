<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Kynx\Saiku\Client\Entity\Schema;

interface SchemaResourceInterface
{
    /**
     * Returns all schemas
     *
     * The schemas returned do not have the XML populated: use `RespositoryResource::getResource($schema->getPath())`
     * if you need it.
     *
     * @return Schema[]
     */
    public function getAll() : array;

    /**
     * Creates a schema, return new
     *
     * The returned schema does not have its XML populated.
     */
    public function create(Schema $schema) : Schema;

    /**
     * Updates an existing schema, returning updated schema
     *
     * If the schema does not exist, it is created. The returned schema does not have its XML populated.
     */
    public function update(Schema $schema) : Schema;

    /**
     * Deletes a schema, if it exists
     */
    public function delete(Schema $schema) : void;
}
