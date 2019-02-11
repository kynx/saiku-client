<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Entity\Datasource;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\DatasourceResource;
use KynxTest\Saiku\Client\AbstractTest;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\DatasourceResource
 */
final class DatasourceResourceTest extends AbstractTest
{
    /**
     * @var DatasourceResource
     */
    private $datasource;

    private $datasources = '[
        {
            "enabled":null,
            "connectionname":"earthquakes",
            "jdbcurl":null,
            "schema":null,
            "driver":null,
            "username":null,
            "password":null,
            "connectiontype":null,
            "id":"4432dd20-fcae-11e3-a3ac-0800200c9a67",
            "path":null,
            "advanced":"type=OLAP\nname=earthquakes\ndriver=mondrian.olap4j.MondrianOlap4jDriver\nlocation=jdbc:mondrian:Jdbc=jdbc:h2:../../data/earthquakes;MODE=MySQL;Catalog=mondrian:///datasources/earthquakes.xml;JdbcDrivers=org.h2.Driver\nusername=sa\npassword=\n",
            "security_type":null,
            "propertyKey":null,
            "csv":null
        },
        {
            "enabled":null,
            "connectionname":"foodmart",
            "jdbcurl":"jdbc:h2:../../data/foodmart",
            "schema":"mondrian:///datasources/foodmart4.xml",
            "driver":"org.h2.Driver",
            "username":"sa",
            "password":"",
            "connectiontype":"MONDRIAN",
            "id":"4432dd20-fcae-11e3-a3ac-0800200c9a66",
            "path":null,
            "advanced":null,
            "security_type":null,
            "propertyKey":null,
            "csv":null
        }
    ]';

    protected function setUp()
    {
        parent::setUp();

        $this->datasource = new DatasourceResource($this->getSessionResource());
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], $this->datasources)
        ]);
        $datasources = $this->datasource->getAll();
        $actual = array_map(function (Datasource $datasource) {
            return $datasource->toArray();
        }, $datasources);

        $expected = $this->getExpectedDatasources();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500)
        ]);
        $this->datasource->getAll();
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201)
        ]);
        $this->datasource->getAll();
    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], '{"connectionname":"foo","connectiontype":"MONDRIAN"}')
        ]);
        $datasource = $this->getValidDatasource();
        $actual = $this->datasource->create($datasource);
        $this->assertEquals($datasource, $actual);
    }

    /**
     * @covers ::create
     */
    public function testCreateSendsJson()
    {
        $json = '{"connectionname":"foo","connectiontype":"MONDRIAN"}';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], $json)
        ]);
        $datasource = $this->getValidDatasource();
        $this->datasource->create($datasource);
        $request = $this->getLastRequest();
        $expected = json_decode($json, true);
        $actual = array_intersect_key(json_decode((string) $request->getBody(), true), $expected);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::create
     */
    public function testCreate500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('500')
        ]);
        $this->datasource->create($this->getValidDatasource());
    }

    /**
     * @covers ::create
     */
    public function testCreate201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('201')
        ]);
        $this->datasource->create($this->getValidDatasource());
    }

    /**
     * @covers ::validate
     */
    public function testCreateNoConnectionTypeThrowsException()
    {
        $this->expectException(EntityException::class);
        $datasource = new Datasource();
        $datasource->setConnectionName('foo');
        $this->datasource->create($datasource);
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], '{"id":"aaa","connectionname":"foo","connectiontype":"MONDRIAN"}')
        ]);
        $datasource = $this->getValidDatasource('aaa');
        $actual = $this->datasource->update($datasource);
        $this->assertEquals($datasource, $actual);
    }

    /**
     * @covers ::update
     */
    public function testUpdateSendsJson()
    {
        $json = '{"id":"aaa","connectionname":"foo","connectiontype":"MONDRIAN"}';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], $json)
        ]);
        $datasource = $this->getValidDatasource('aaa');
        $this->datasource->update($datasource);
        $request = $this->getLastRequest();
        $expected = json_decode($json, true);
        $actual = array_intersect_key(json_decode((string) $request->getBody(), true), $expected);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::update
     */
    public function testUpdateNoIdThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->datasource->update($this->getValidDatasource());
    }

    /**
     * @covers ::update
     */
    public function testUpdate500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('500')
        ]);
        $this->datasource->update($this->getValidDatasource('aaa'));
    }

    /**
     * @covers ::update
     */
    public function testUpdate201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('201')
        ]);
        $this->datasource->update($this->getValidDatasource('aaa'));
    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('200', [], '{"id":"aaa","connectionname":"foo","connectiontype":"MONDRIAN"}')
        ]);
        $datasource = $this->getValidDatasource('aaa');
        $this->datasource->delete($datasource);
        $request = $this->getLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
    }

    /**
     * @covers ::delete
     */
    public function testDeleteNoIdThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->datasource->delete($this->getValidDatasource());
    }

    /**
     * @covers ::delete
     */
    public function testDelete500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response('500')
        ]);
        $this->datasource->delete($this->getValidDatasource('aaa'));
    }

    private function getExpectedDatasources()
    {
        // @todo - do we really want to discard these?
        $discard = array_flip(['enabled', 'security_type', 'propertyKey', 'csv']);
        return array_map(function ($datasource) use ($discard) {
            return array_diff_key($datasource, $discard);
        }, json_decode($this->datasources, true));
    }

    private function getValidDatasource(?string $id = null): Datasource
    {
        $datasource = new Datasource($id ? ['id' => $id] : null);
        $datasource->setConnectionName('foo')
            ->setConnectionType('MONDRIAN');
        return $datasource;
    }
}
