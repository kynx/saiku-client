<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Kynx\Saiku\Client\Entity\Datasource;

interface DatasourceResourceInterface
{
    /**
     * Returns array of all datasources
     * @return Datasource[]
     */
    public function getAll() : array;

    /**
     * Creates new datasource
     */
    public function create(Datasource $datasource) : Datasource;

    /**
     * Updates existing datasource
     */
    public function update(Datasource $datasource) : Datasource;

    /**
     * Deletes existing datasource
     */
    public function delete(Datasource $datasource) : void;
}
