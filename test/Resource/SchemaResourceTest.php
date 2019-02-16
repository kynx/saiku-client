<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Entity\Schema;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\SchemaResource;
use KynxTest\Saiku\Client\AbstractTest;

use function json_encode;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\SchemaResource
 */
class SchemaResourceTest extends AbstractTest
{
    // phpcs:disable
    private const XML  = '<?xml version=\'1.0\'?><Schema name=\'Global Earthquakes\' metamodelVersion=\'4.0\'></Schema>';
    // phpcs:enable

    /** @var SchemaResource */
    private $schema;

    protected function setUp()
    {
        parent::setUp();

        $this->schema = new SchemaResource($this->getSessionResource());
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '[{"name":"foo","path":"/foo"}]'),
        ]);
        $actual = $this->schema->getAll();
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(Schema::class, $actual[0]);
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->schema->getAll();
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->schema->getAll();
    }

    /**
     * @covers ::create
     * @covers ::validate
     */
    public function testCreate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '{}'),
        ]);
        $schema = new Schema();
        $schema->setName('foo')
            ->setPath('/foo')
            ->setXml(self::XML);
        $this->schema->create($schema);

        $formPart = "Content-Disposition: form-data; name=\"name\"\r\nContent-Length: 3\r\n\r\nfoo\r\n";
        $filePart = "Content-Disposition: form-data; name=\"file\"\r\nContent-Length: 87\r\n\r\n" . self::XML . "\r\n";

        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertStringContainsString($formPart, (string) $request->getBody());
        $this->assertStringContainsString($filePart, (string) $request->getBody());
    }

    /**
     * @covers ::create
     */
    public function testCreateReturnsSchema()
    {
        $schema = new Schema();
        $schema->setName('foo')
            ->setPath('/foo')
            ->setXml(self::XML);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode($schema->toArray())),
        ]);
        $actual = $this->schema->create($schema);
        $this->assertInstanceOf(Schema::class, $actual);
        $this->assertEquals($schema, $actual);
    }

    /**
     * @covers ::create
     */
    public function testCreate500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->schema->create(new Schema('{"name":"foo"}'));
    }

    /**
     * @covers ::create
     */
    public function testCreate201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->schema->create(new Schema('{"name":"foo"}'));
    }

    /**
     * @covers ::validate
     */
    public function testCreateNoNameThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->schema->create(new Schema());
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '{}'),
        ]);
        $schema = new Schema();
        $schema->setName('foo')
            ->setPath('/foo')
            ->setXml(self::XML);
        $this->schema->update($schema);

        $formPart = "Content-Disposition: form-data; name=\"name\"\r\nContent-Length: 3\r\n\r\nfoo\r\n";
        $filePart = "Content-Disposition: form-data; name=\"file\"\r\nContent-Length: 87\r\n\r\n" . self::XML . "\r\n";

        $request = $this->getLastRequest();
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertStringContainsString($formPart, (string) $request->getBody());
        $this->assertStringContainsString($filePart, (string) $request->getBody());
    }

    /**
     * @covers ::update
     */
    public function testUpdateReturnsSchema()
    {
        $schema = new Schema();
        $schema->setName('foo')
            ->setPath('/foo')
            ->setXml(self::XML);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode($schema->toArray())),
        ]);
        $actual = $this->schema->update($schema);
        $this->assertInstanceOf(Schema::class, $actual);
        $this->assertEquals($schema, $actual);
    }

    /**
     * @covers ::update
     */
    public function testUpdate500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->schema->update(new Schema('{"name":"foo"}'));
    }

    /**
     * @covers ::update
     */
    public function testUpdate201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->schema->update(new Schema('{"name":"foo"}'));
    }

    /**
     * @covers ::validate
     */
    public function testUpdateNoNameThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->schema->update(new Schema());
    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(20),
        ]);
        $schema = new Schema();
        $schema->setName('foo');
        $this->schema->delete($schema);

        $request = $this->getLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/foo', $request->getUri()->getPath());
    }

    /**
     * @covers ::delete
     */
    public function testDeleteAddsAcceptHeader()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(20),
        ]);
        $schema = new Schema();
        $schema->setName('foo');
        $this->schema->delete($schema);

        $request = $this->getLastRequest();
        $this->assertEquals(['application/json, text/javascript, */*; q=0.01'], $request->getHeader('Accept'));
    }

    /**
     * @covers ::delete
     */
    public function testDelete500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $schema = new Schema();
        $schema->setName('foo');
        $this->schema->delete($schema);
    }

    /**
     * @covers ::validate
     */
    public function testDeleteNoNameThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->schema->delete(new Schema());
    }
}
