<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\RepositoryResource;
use KynxTest\Saiku\Client\AbstractTest;

use function explode;
use function json_encode;
use function parse_str;
use function parse_url;

use const PHP_URL_QUERY;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\RepositoryResource
 */
final class RepositoryResourceTest extends AbstractTest
{
    /** @var RepositoryResource */
    private $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->repo = new RepositoryResource($this->getSessionResource());
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $file1   = new File('{"path":"/foo.saiku"}');
        $file2   = new File('{"path"::/bar.saiku"}');
        $listing = [$file1->toArray(), $file2->toArray()];
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode($listing)),
        ]);
        $actual = $this->repo->get();
        $this->assertEquals('/', $actual->getPath());
        $this->assertEquals([$file1, $file2], $actual->getRepoObjects());
    }

    /**
     * @covers ::get
     */
    public function testGetRequestsPath()
    {
        $file1   = new File('{"path":"/homes/foo.saiku"}');
        $file2   = new File('{"path"::/homes/bar.saiku"}');
        $listing = [$file1->toArray(), $file2->toArray()];
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode([['path' => '/homes', 'repoObjects' => $listing]])),
        ]);
        $actual = $this->repo->get('/homes');
        $this->assertEquals('/homes', $actual->getPath());
        $this->assertEquals([$file1, $file2], $actual->getRepoObjects());

        $request = $this->getLastRequest();
        parse_str(parse_url((string) $request->getUri(), PHP_URL_QUERY), $query);
        $this->assertEquals('/homes', $query['path']);
    }

    /**
     * @covers ::get
     */
    public function testGetRequestsContent()
    {
        $file1 = new File('{"fileType":"' . File::FILETYPE_REPORT . '","path":"/foo.saiku"}');
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode([$file1->toArray()])),
            new Response(200, [], 'foo'),
        ]);
        $actual = $this->repo->get(null, true);
        $this->assertEquals('foo', $actual->getRepoObjects()[0]->getContent());
    }

    /**
     * @covers ::get
     */
    public function testGetRequestsDefaultTypes()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '[]'),
        ]);
        $this->repo->get();
        $request = $this->getLastRequest();
        parse_str(parse_url((string) $request->getUri(), PHP_URL_QUERY), $query);
        $this->assertEquals(File::getAllFiletypes(), explode(',', $query['type']));
    }

    /**
     * @covers ::get
     */
    public function testGetRequestsTypes()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '[]'),
        ]);
        $types = [File::FILETYPE_REPORT, File::FILETYPE_DATASOURCE];
        $this->repo->get(null, false, $types);
        $request = $this->getLastRequest();
        parse_str(parse_url((string) $request->getUri(), PHP_URL_QUERY), $query);
        $this->assertEquals($types, explode(',', $query['type']));
    }

    /**
     * @covers ::get
     */
    public function testGet500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->repo->get();
    }

    /**
     * @covers ::get
     */
    public function testGet201ThrowsExcepton()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->repo->get();
    }

    /**
     * @covers ::getResource
     */
    public function testGetResource()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], 'foo'),
        ]);
        $actual = $this->repo->getResource('/foo');
        $this->assertEquals('foo', $actual);
        $request = $this->getLastRequest();
        parse_str(parse_url((string) $request->getUri(), PHP_URL_QUERY), $query);
        $this->assertEquals('/foo', $query['file']);
    }

    /**
     * @covers ::getResource
     */
    public function testGetResource500ThrowsExceptionWithHint()
    {
        $this->expectException(SaikuException::class);
        $this->expectExceptionMessage("Error getting '/foo'. Are you sure it exists?");
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->repo->getResource('/foo');
    }

    /**
     * @covers ::getResource
     */
    public function testGetResource404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404),
        ]);
        $this->repo->getResource('/foo');
    }

    /**
     * @covers ::getResource
     */
    public function testGetResource201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->repo->getResource('/foo');
    }
}
