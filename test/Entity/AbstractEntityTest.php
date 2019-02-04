<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\AbstractEntity;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\AbstractEntity
 */
class AbstractEntityTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructorPopulatesJson()
    {
        $instance = $this->getInstance('{"foo":"bar"}');
        $this->assertEquals('bar', $instance->getFoo());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorPopulatesArray()
    {
        $instance = $this->getInstance(['foo' => 'bar']);
        $this->assertEquals('bar', $instance->getFoo());
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorHandlesNull()
    {
        $instance = $this->getInstance();
        $this->assertNull($instance->getFoo());
    }

    /**
     * @covers ::__toString
     */
    public function testToStringReturnsJson()
    {
        $instance = $this->getInstance(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', (string) $instance);
    }

    /**
     * @covers ::toArray
     */
    public function testToArrayReturnsVars()
    {
        $instance = $this->getInstance(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateSetsProperty()
    {
        $instance = $this->getInstance(['foo' => 'bar']);
        $this->assertEquals('bar', $instance->getFoo());
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateIgnoresNonProperties()
    {
        $instance = $this->getInstance(['bar' => 'baz']);
        $this->assertEquals(['foo' => null], $instance->getObjectVars());
    }

    /**
     * @covers ::extract
     */
    public function testExtractReturnsVars()
    {
        $instance = $this->getInstance(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
    }

    private function getInstance($properties = null)
    {
        return new class($properties) extends AbstractEntity {
            protected $foo;

            public function getFoo()
            {
                return $this->foo;
            }

            public function getObjectVars()
            {
                return get_object_vars($this);
            }
        };
    }
}
