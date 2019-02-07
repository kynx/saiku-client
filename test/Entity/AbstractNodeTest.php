<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Exception\EntityException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\AbstractNode
 */
class AbstractNodeTest extends TestCase
{
    /**
     * @covers ::getInstance
     */
    public function testGetInstanceConsumesJson()
    {
        $actual = AbstractNode::getInstance('{"type":"'. AbstractNode::TYPE_FILE . '"}');
        $this->assertInstanceOf(File::class, $actual);
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstanceConsumesArray()
    {
        $actual = AbstractNode::getInstance(["type" => AbstractNode::TYPE_FILE]);
        $this->assertInstanceOf(File::class, $actual);
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstanceInvalidTypeThrowsHydrationException()
    {
        $this->expectException(EntityException::class);
        AbstractNode::getInstance(666);
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstanceUnknownTypeThrowsHydrationException()
    {
        $this->expectException(EntityException::class);
        AbstractNode::getInstance('{"type":"foo"}');
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstanceReturnsFolder()
    {
        $actual = AbstractNode::getInstance(["type" => AbstractNode::TYPE_FOLDER]);
        $this->assertInstanceOf(Folder::class, $actual);
    }

    /**
     * @covers ::getInstance
     */
    public function testGetInstanceReturnsFile()
    {
        $actual = AbstractNode::getInstance(["type" => AbstractNode::TYPE_FILE]);
        $this->assertInstanceOf(File::class, $actual);
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateSetsJavaClass()
    {
        $instance = $this->getInstance(['@class' => 'foo']);
        $this->assertEquals('foo', $instance->getJavaClass());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateAcceptsMissingClass()
    {
        $instance = $this->getInstance();
        $this->assertNull($instance->getJavaClass());
    }

    /**
     * @covers ::extract
     */
    public function testExtractSetsClass()
    {
        $instance = $this->getInstance(['@class' => 'foo']);
        $extracted = $instance->toArray();
        $this->assertArrayNotHasKey('javaClass', $extracted);
        $this->assertEquals('foo', $extracted['@class']);
    }

    /**
     * @covers ::extract
     */
    public function testExtractSetsType()
    {
        $instance = $this->getInstance();
        $extracted = $instance->toArray();
        $this->assertEquals(AbstractNode::TYPE_FILE, $extracted['type']);
    }

    /**
     * @covers ::extract
     */
    public function testExtractSetsFolderType()
    {
        $instance = new Folder();
        $extracted = $instance->toArray();
        $this->assertEquals(AbstractNode::TYPE_FOLDER, $extracted['type']);
    }

    /**
     * @covers ::getId
     */
    public function testGetId()
    {
        $instance = $this->getInstance(['id' => 'foo']);
        $this->assertEquals('foo', $instance->getId());
    }

    /**
     * @covers ::getJavaClass
     */
    public function testGetJavaClass()
    {
        $instance = $this->getInstance(['@class' => 'foo']);
        $this->assertEquals('foo', $instance->getJavaClass());
    }

    /**
     * @covers ::setName
     * @covers ::getName
     */
    public function testSetName()
    {
        $instance = $this->getInstance();
        $instance->setName('foo');
        $this->assertEquals('foo', $instance->getName());
    }

    /**
     * @covers ::setPath
     * @covers ::getPath
     */
    public function testSetPath()
    {
        $instance = $this->getInstance();
        $instance->setPath('/homes/foo');
        $this->assertEquals('/homes/foo', $instance->getPath());
    }

    /**
     * @covers ::setAcl
     * @covers ::getAcl
     */
    public function testSetAcl()
    {
        $instance = $this->getInstance();
        $instance->setAcl(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN'], $instance->getAcl());
    }

    private function getInstance(array $properties = [])
    {
        return new class($properties) extends AbstractNode {};
    }
}
