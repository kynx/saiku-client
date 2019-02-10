<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\RepositoryResource;
use Kynx\Saiku\Client\Resource\SchemaResource;

/**
 * @group integration
 * @coversNothing
 */
final class SchemaResourceTest extends AbstractIntegrationTest
{
    private const NAME = 'foodmart4.xml';
    private const PATH = '/datasources/foodmart4.xml';
    private const XML = '<?xml version=\'1.0\'?><Schema name=\'Global Earthquakes\' metamodelVersion=\'4.0\'></Schema>';
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
            ->setXml(self::XML);
        $this->schema->create($schema);

        $created = $this->getSchema('foo.xml');
        $expected = $schema->toArray();
        $expected['xml'] = null; // we don't get this back in the response
        $actual = $created->toArray();
        $this->assertEquals($expected, $actual);
        $xml = $this->getContent('/datasources/foo.xml');
        $this->assertEquals(self::XML, $xml);
    }

    public function testUpdate()
    {
        $schema = new Schema();
        $schema->setName(self::NAME)
            ->setPath(self::PATH)
            ->setXml(self::XML);

        $this->schema->update($schema);
        $xml = $this->getContent(self::PATH);
        $this->assertEquals(self::XML, $xml);
    }

//    Unlike just about every other service, updating a non-existent schema creates a new one
//    public function testUpdateNonExistentThrowsException()
//    {
//        $this->expectException(SaikuException::class);
//        $schema = new Schema();
//        $schema->setName('bar.xml')
//            ->setPath(self::PATH)
//            ->setXml(self::XML);
//
//        $this->schema->update($schema);
//    }

    public function testDelete()
    {
        $schema = new Schema();
        $schema->setName(self::NAME);
        $this->schema->delete($schema);
        $actual = $this->getSchema(self::NAME);
        $this->assertNull($actual);
    }

    public function testDeleteNonExistentThrowsNoWobblies()
    {
        $schema = new Schema();
        $schema->setName('bar.xml')
            ->setPath(self::PATH)
            ->setXml(self::XML);
        $this->assertTrue(true);
    }

    private function getSchema(string $name): ?Schema
    {
        return array_reduce($this->schema->getAll(), function ($carry, Schema $schema) use ($name) {
            return $schema->getName() == $name ? $schema : $carry;
        }, null);
    }

    private function getContent($path): string
    {
        $repo = new RepositoryResource($this->session);
        return $repo->getResource($path);
    }
}
