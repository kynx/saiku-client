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
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\Folder
 */
class FolderTest extends TestCase
{
    /**
     * @var Folder
     */
    private $folder;

    protected function setUp()
    {
        $this->folder = new Folder();
    }

    /**
     * @covers ::hydrate
     */
    public function testHydratePopulatesRepoObjects()
    {
        $properties = [
            'repoObjects' => [
                [
                    'type' => AbstractNode::TYPE_FILE,
                ],
            ],
        ];
        $folder = new Folder($properties);
        $actual = $folder->getRepoObjects();
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(File::class, $actual[0]);
    }

    /**
     * @covers ::hydrate
     */
    public function testHydrateHandlesEmptyRepoObjects()
    {
        $folder = new Folder();
        $this->assertCount(0, $folder->getRepoObjects());
    }

    /**
     * @covers ::extract
     */
    public function testExtractPopulatesRepoObjects()
    {
        $file = new File(['path' => '/homes/foo']);
        $this->folder->setRepoObjects([$file]);
        $actual = $this->folder->toArray();
        $this->assertArrayHasKey('repoObjects', $actual);
        $object = $actual['repoObjects'][0];
        $this->assertEquals('/homes/foo', $object['path']);
    }

    /**
     * @covers ::setRepoObjects
     * @covers ::getRepoObjects
     */
    public function testSetRepoObjects()
    {
        $file = new File(['path' => '/homes/foo']);
        $this->folder->setRepoObjects([$file]);
        $actual = $this->folder->getRepoObjects();
        $this->assertCount(1, $actual);
        $this->assertEquals($file, $actual[0]);
    }
}
