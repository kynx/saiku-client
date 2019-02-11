<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\RepositoryResource;

/**
 * @group integration
 * @coversNothing
 */
final class RepositoryResourceTest extends AbstractIntegrationTest
{
    private const REPORT_PATH = '/homes/home:admin/sample_reports/average_mag_and_depth_over_time.saiku';
    private const SCHEMA_PATH = '/datasources/foodmart4.xml';
    private const NONEXISTENT_PATH = '/homes/home:admin/nothere.saiku';

    /**
     * @var RepositoryResource
     */
    private $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new RepositoryResource($this->session);
    }


    public function testGetReturnsRoot()
    {
        $repo = $this->repo->get();
        $actual = array_map(function (AbstractNode $node) {
            return $node->getName();
        }, $repo->getRepoObjects());
        $expected = ['datasources', 'etc', 'homes'];
        $this->assertEquals($expected, $actual);
    }

    public function testGetReturnsPath()
    {
        $repo = $this->repo->get('/homes');
        $actual = array_map(function (AbstractNode $node) {
            return $node->getName();
        }, $repo->getRepoObjects());
        $expected = ['home:admin', 'home:smith'];
        $this->assertEquals($expected, $actual);
    }

    public function testGetReturnsContent()
    {
        $repo = $this->repo->get(null, true);
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayHasKey(self::REPORT_PATH, $flattened);
        $actual = $flattened[self::REPORT_PATH];
        /* @var File $actual */
        $this->assertNotEmpty($actual->getContent());
    }

    public function testGetFiltersTypes()
    {
        $repo = $this->repo->get(null, false, [File::FILETYPE_SCHEMA]);
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayNotHasKey(self::REPORT_PATH, $flattened);
        $this->assertArrayHasKey(self::SCHEMA_PATH, $flattened);
    }

    public function testGetFileReturnsFile()
    {
        $file = $this->repo->get(self::REPORT_PATH);
        $this->assertInstanceOf(File::class, $file);
    }

    public function testGetResourceReturnsContent()
    {
        $resource = $this->repo->getResource(self::REPORT_PATH);
        $this->assertIsString($resource);
        $actual = json_decode($resource, true);
        $this->assertIsArray($actual);
    }

    public function testGetNonExistentResourceThrowsNotFoundException()
    {
        $this->expectException(SaikuException::class);
        $this->repo->getResource(self::NONEXISTENT_PATH);
    }

    public function testStoreResourceStoresFile()
    {
        $file = new File();
        $file->setFileType($file::FILETYPE_REPORT);
        $file->setPath(self::NONEXISTENT_PATH);
        $file->setName('foo.saiku');
        $file->setAcl(['ROLE_USER']);
        $file->setContent('{"foo":"bar"}');

        $this->repo->storeResource($file);
        $actual = $this->repo->getResource(self::NONEXISTENT_PATH);
        $this->assertEquals('{"foo":"bar"}', $actual);
    }

    public function testStoreResourceOverwritesFile()
    {
        $file = new File();
        $file->setFileType($file::FILETYPE_REPORT);
        $file->setPath(self::NONEXISTENT_PATH);
        $file->setName('foo.saiku');
        $file->setAcl(['ROLE_USER']);
        $file->setContent('{"foo":"bar"}');
        $this->repo->storeResource($file);

        $file->setContent('{"bar":"baz"}');
        $this->repo->storeResource($file);
        $actual = $this->repo->getResource(self::NONEXISTENT_PATH);
        $this->assertEquals('{"bar":"baz"}', $actual);
    }

    public function testStoreResourceStoresFolder()
    {
        $folder = new Folder();
        $folder->setPath('/homes/home:smith/foo');
        $folder->setName('foo');
        $folder->setAcl(['ROLE_USER']);
        $this->repo->storeResource($folder);

        $repo = $this->repo->get();
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayHasKey('/homes/home:smith/foo', $flattened);
    }

    public function testDeleteResourceDeletes()
    {
        $file = new File();
        $file->setPath(self::REPORT_PATH);
        $this->repo->deleteResource($file);

        $repo = $this->repo->get();
        $flattened = iterator_to_array($this->flattenRepo($repo));
        $this->assertArrayNotHasKey(self::REPORT_PATH, $flattened);
    }

    public function testDeleteNonExistentResourceThrowsNoWobblies()
    {
        $file = new File();
        $file->setPath(self::NONEXISTENT_PATH);
        $this->repo->deleteResource($file);
        $this->assertTrue(true);
    }

    public function testGetAclReturnsAcl()
    {
        $actual = $this->repo->getAcl(self::REPORT_PATH);
        $this->assertInstanceOf(Acl::class, $actual);
    }

    public function testGetNonExistentAclThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $this->repo->getAcl(self::NONEXISTENT_PATH);
    }

    public function testSetAclSetsAcl()
    {
        $acl = new Acl();
        $acl->setOwner('smith')
            ->setType($acl::TYPE_PUBLIC)
            ->addRole('ROLE_USER', [$acl::METHOD_READ]);
        $this->repo->setAcl(self::REPORT_PATH, $acl);

        $actual = $this->repo->getAcl(self::REPORT_PATH);
        $this->assertEquals($acl, $actual);
    }

    public function testSetAclWithUsersSetsAcl()
    {
        $acl = new Acl();
        $acl->setOwner('admin')
            ->setType($acl::TYPE_SECURED)
            ->addRole('ROLE_ADMIN', [$acl::METHOD_GRANT])
            ->addUser('smith', [$acl::METHOD_READ]);

        $this->repo->setAcl(self::REPORT_PATH, $acl);

        $actual = $this->repo->getAcl(self::REPORT_PATH);
        $this->assertEquals($acl, $actual);
    }

    public function testSetAclNonExistentPathThrowsSaikuException()
    {
        $this->expectException(SaikuException::class);
        $acl = new Acl();
        $acl->setOwner('smith')
            ->setType($acl::TYPE_PUBLIC)
            ->addRole('ROLE_USER', [$acl::METHOD_READ]);
        $this->repo->setAcl(self::NONEXISTENT_PATH, $acl);
    }

    private function flattenRepo(Folder $folder)
    {
        foreach ($folder->getRepoObjects() as $object) {
            yield $object->getPath() => $object;

            if ($object instanceof Folder) {
                foreach ($this->flattenRepo($object) as $path => $child) {
                    yield $path => $child;
                }
            }
        }
    }
}
