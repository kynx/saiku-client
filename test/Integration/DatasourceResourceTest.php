<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Integration;

use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Resource\DatasourceResource;

use function array_reduce;

/**
 * @group integration
 * @coversNothing
 */
class DatasourceResourceTest extends AbstractIntegrationTest
{
    public const DATASOURCE_NAME = 'foodmart';

    /** @var DatasourceResource */
    private $datasource;

    protected function setUp()
    {
        parent::setUp();
        $this->datasource = new DatasourceResource($this->session);
    }

    public function testGetAll()
    {
        $datasources = $this->datasource->getAll();
        $this->assertCount(2, $datasources);
        foreach ($datasources as $datasource) {
            $this->assertInstanceOf(Datasource::class, $datasource);
        }
    }

    public function testCreate()
    {
        $datasource = new Datasource();
        $datasource->setPath('/datasources/foo.sds')
            ->setConnectionType('MONDRIAN')
            ->setConnectionName('foo')
            ->setSchema('Foodmart')
            ->setUsername('foo')
            ->setPassword('bar');
        $this->datasource->create($datasource);

        $created = $this->getDatasource('foo');
        $this->assertInstanceOf(Datasource::class, $created);
        $expected       = $datasource->toArray();
        $actual         = $created->toArray();
        $expected['id'] = $actual['id'];
        $this->assertEquals($expected, $actual);
    }

    public function testUpdate()
    {
        $datasource = $this->getDatasource(self::DATASOURCE_NAME);
        $datasource->setSchema('Earthquakes');
        $this->datasource->update($datasource);
        $actual = $this->getDatasource(self::DATASOURCE_NAME);
        $this->assertEquals($datasource, $actual);
    }

    public function testDelete()
    {
        $datasource = $this->getDatasource(self::DATASOURCE_NAME);
        $this->datasource->delete($datasource);
        $actual = $this->getDatasource(self::DATASOURCE_NAME);
        $this->assertNull($actual);
    }

    private function getDatasource(string $connectionName) : ?Datasource
    {
        return array_reduce($this->datasource->getAll(), function ($carry, Datasource $ds) use ($connectionName) {
            return $ds->getConnectionName() === $connectionName ? $ds : $carry;
        }, null);
    }
}
