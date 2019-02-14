<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\RepositoryResource;
use KynxTest\Saiku\Client\AbstractTest;

use function explode;
use function json_encode;
use function parse_str;
use function parse_url;
use function urlencode;

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
    public function testGetPopulatesContent()
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
     * @covers ::populateFolderContents
     */
    public function testGetRecursesContent()
    {
        $file = new File();
        $file->setFileType(File::FILETYPE_REPORT);
        $file->setPath('/foo/bar.saiku');
        $folder = new Folder();
        $folder->setPath('/foo');
        $folder->setRepoObjects([$file]);

        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode([$folder->toArray()])),
            new Response(200, [], 'foo'),
        ]);
        $actual = $this->repo->get('/foo', true);
        $this->assertInstanceOf(Folder::class, $actual);
        /** @var Folder $actual */
        $this->assertCount(1, $actual->getRepoObjects());
        $this->assertEquals('foo', $actual->getRepoObjects()[0]->getContent());
    }

    /**
     * @covers ::populateFolderContents
     */
    public function testGetIgnoresNonContentType()
    {
        $file1 = new File('{"fileType":"' . File::FILETYPE_DATASOURCE . '","path":"/foo.sds"}');
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], json_encode([$file1->toArray()])),
        ]);
        $actual = $this->repo->get('/foo.sds', true);
        $this->assertInstanceOf(File::class, $actual);
        /** @var File $actual */
        $this->assertNull($actual->getContent());
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

    /**
     * @covers ::storeResource
     */
    public function testStoreResourceFolder()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200),
        ]);
        $folder = new Folder();
        $folder->setPath('/homes/foo');
        $this->repo->storeResource($folder);
        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('file=%2Fhomes%2Ffoo', (string) $request->getBody());
    }

    /**
     * @covers ::storeResource
     */
    public function testStoreResourceFile()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200),
        ]);
        $file = new File();
        $file->setPath('/homes/foo');
        $file->setContent('blah');
        $this->repo->storeResource($file);
        $request = $this->getLastRequest();
        $this->assertEquals('file=%2Fhomes%2Ffoo&content=blah', (string) $request->getBody());
    }

    /**
     * @covers ::storeResource
     */
    public function testStoreResource500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $folder = new Folder();
        $folder->setPath('/homes/foo');
        $this->repo->storeResource($folder);
    }

    /**
     * @covers ::deleteResource
     */
    public function testDeleteResource()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200),
        ]);
        $file = new File();
        $file->setPath('/homes/foo');
        $this->repo->deleteResource($file);
        $request = $this->getLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('file=%2Fhomes%2Ffoo', $request->getUri()->getQuery());
    }

    /**
     * @covers ::deleteResource
     */
    public function testDeleteResource500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $file = new File();
        $file->setPath('/homes/foo');
        $this->repo->deleteResource($file);
    }

    /**
     * @covers ::getAcl
     */
    public function testGetAcl()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '{"owner":"","type":"SECURED","roles":{"ROLE_USER":["READ"]},"users":[]}'),
        ]);
        $actual = $this->repo->getAcl('/homes/foo');
        $this->assertInstanceOf(Acl::class, $actual);
        $request = $this->getLastRequest();
        $this->assertEquals('file=%2Fhomes%2Ffoo', $request->getUri()->getQuery());
    }

    /**
     * @covers ::getAcl
     */
    public function testGetNoAclReturnsEmptyAcl()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '{"owner":null,"type":null,"roles":null,"users":null}'),
        ]);
        $actual   = $this->repo->getAcl('/homes/foo');
        $expected = new Acl();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getAcl
     */
    public function testGetAcl500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->repo->getAcl('/homes/foo');
    }

    /**
     * @covers ::getAcl
     */
    public function testGetAcl201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->repo->getAcl('/homes/foo');
    }

    /**
     * @covers ::setAcl
     */
    public function testSetAcl()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200),
        ]);
        $acl = new Acl();
        $acl->setType(Acl::TYPE_SECURED)
            ->addRole('ROLE_USER', [Acl::METHOD_READ]);
        $this->repo->setAcl('/homes/foo', $acl);
        $encoded = urlencode(json_encode($acl->toArray()));
        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('file=%2Fhomes%2Ffoo&acl=' . $encoded, (string) $request->getBody());
    }

    /**
     * @covers ::setAcl
     */
    public function testSetAcl500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->repo->setAcl('/homes/foo', new Acl());
    }
}
