<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Resource\SchemaResource;

/**
 * @group integration
 * @coversNothing
 */
final class SchemaResourceTest extends AbstractIntegrationTest
{
    /**
     * @var SchemaResource
     */
    private $schema;

    protected function setUp()
    {
        parent::setUp();
        $this->schema = new SchemaResource($this->session);
    }


    public function testGetAll()
    {
        $schemas = $this->schema->getAll();
        $this->assertCount(2, $schemas);
        foreach ($schemas as $schema) {
            $this->assertInstanceOf(Schema::class, $schema);
            $this->assertNull($schema->getXml());
        }
    }

    public function testCreate()
    {
        $schema = new Schema();
        $schema->setName('foo.xml')
            ->setPath('/datasources/foo.xml')
            ->setXml('<?xml version=\'1.0\'?><Schema name=\'Global Earthquakes\' metamodelVersion=\'4.0\'></Schema>');
        $this->schema->create($schema);

        $created = $this->getSchema('foo.xml');
        $this->assertEquals($schema, $created);
    }

    private function getSchema(string $name): ?Schema
    {
        return array_reduce($this->schema->getAll(), function ($carry, Schema $schema) use ($name) {
            return $schema->getName() == $name ? $schema : $carry;
        }, null);
    }
}
