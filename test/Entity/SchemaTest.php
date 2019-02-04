<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\Schema;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\Schema
 */
class SchemaTest extends TestCase
{
    /**
     * @var Schema
     */
    private $schema;

    protected function setUp()
    {
        $this->schema = new Schema();
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateStripsXmlExtension()
    {
        $schema = new Schema(['name' => 'earthquakes.xml']);
        $this->assertEquals('earthquakes', $schema->getName());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateWithOtherExtension()
    {
        $schema = new Schema(['name' => 'earthquakes.info']);
        $this->assertEquals('earthquakes.info', $schema->getName());
    }

    /**
     * @covers ::setXml
     * @covers ::getXml
     */
    public function testSetXml()
    {
        $this->schema->setXml('<root><element/></root>');
        $this->assertEquals('<root><element/></root>', $this->schema->getXml());
    }

    /**
     * @covers ::setType
     * @covers ::getType
     */
    public function testSetType()
    {
        $this->schema->setType('MONDRIAN');
        $this->assertEquals('MONDRIAN', $this->schema->getType());
    }

    /**
     * @covers ::setPath
     * @covers ::getPath
     */
    public function testSetPath()
    {
        $this->schema->setPath('/etc/datasources/earthquakes.xml');
        $this->assertEquals('/etc/datasources/earthquakes.xml', $this->schema->getPath());
    }

    /**
     * @covers ::setName
     * @covers ::getName
     */
    public function testSetName()
    {
        $this->schema->setName('earthquakes');
        $this->assertEquals('earthquakes', $this->schema->getName());
    }
}
