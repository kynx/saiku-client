<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\Datasource;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\Datasource
 */
class DatasourceTest extends TestCase
{
    /**
     * @var Datasource
     */
    private $datasource;

    protected function setUp()
    {
        $this->datasource = new Datasource();
    }

    /**
     * @covers ::getId
     */
    public function testGetId()
    {
        $datasource = new Datasource(['id' => 'idiot']);
        $this->assertEquals('idiot', $datasource->getId());
    }

    /**
     * @covers ::setDriver
     * @covers ::getDriver
     */
    public function testSetDriver()
    {
        $this->datasource->setDriver('org.h2.Driver');
        $this->assertEquals('org.h2.Driver', $this->datasource->getDriver());
    }

    /**
     * @covers ::setConnectionType
     * @covers ::getConnectionType
     */
    public function testSetConnectionType()
    {
        $this->datasource->setConnectionType('MONDRIAN');
        $this->assertEquals('MONDRIAN', $this->datasource->getConnectionType());
    }

    /**
     * @covers ::setConnectionName
     * @covers ::getConnectionName
     */
    public function testSetConnectionName()
    {
        $this->datasource->setConnectionName('earthquakes');
        $this->assertEquals('earthquakes', $this->datasource->getConnectionName());
    }

    /**
     * @covers ::setUsername
     * @covers ::getUsername
     */
    public function testSetUsername()
    {
        $this->datasource->setUsername('sa');
        $this->assertEquals('sa', $this->datasource->getUsername());
    }

    /**
     * @covers ::setJdbcUrl
     * @covers ::getJdbcUrl
     */
    public function testSetJdbcUrl()
    {
        $this->datasource->setJdbcUrl('jdbc:h2:../../data/foodmart');
        $this->assertEquals('jdbc:h2:../../data/foodmart', $this->datasource->getJdbcUrl());
    }

    /**
     * @covers ::setPath
     * @covers ::getPath
     */
    public function testSetPath()
    {
        $this->datasource->setPath('/etc/datasources/earthquakes.xml');
        $this->assertEquals('/etc/datasources/earthquakes.xml', $this->datasource->getPath());
    }

    /**
     * @covers ::setPassword
     * @covers ::getPassword
     */
    public function testSetPassword()
    {
        $this->datasource->setPassword('secret');
        $this->assertEquals('secret', $this->datasource->getPassword());
    }

    /**
     * @covers ::setSchema
     * @covers ::getSchema
     */
    public function testSetSchema()
    {
        $this->datasource->setSchema('earthquakes');
        $this->assertEquals('earthquakes', $this->datasource->getSchema());
    }

    /**
     * @covers ::setAdvanced
     * @covers ::getAdvanced
     */
    public function testSetAdvanced()
    {
        $this->datasource->setAdvanced("foo=bar\njolly=hockey sticks");
        $this->assertEquals("foo=bar\njolly=hockey sticks", $this->datasource->getAdvanced());
    }
}
