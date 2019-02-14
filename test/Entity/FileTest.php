<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Entity;

use Kynx\Saiku\Client\Entity\File;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Entity\File
 */
class FileTest extends TestCase
{
    /** @var File */
    private $file;

    protected function setUp()
    {
        $this->file = new File();
    }

    /**
     * @covers ::setContent
     * @covers ::getContent
     */
    public function testSetContent()
    {
        $this->file->setContent('some content');
        $this->assertEquals('some content', $this->file->getContent());
    }

    /**
     * @covers ::hasContent
     * @dataProvider contentProvider
     */
    public function testHasContent($type, $hasContent)
    {
        $this->file->setFileType($type);
        $this->assertEquals($hasContent, $this->file->hasContent());
    }

    public function contentProvider()
    {
        return [
            [File::FILETYPE_DATASOURCE, false],
            [File::FILETYPE_REPORT, true],
            [File::FILETYPE_SCHEMA, true],
            [File::FILETYPE_LICENSE, true],
        ];
    }

    /**
     * @covers ::getAllFiletypes
     */
    public function testGetAllFiletypes()
    {
        $expected = [
            File::FILETYPE_DATASOURCE,
            File::FILETYPE_LICENSE,
            File::FILETYPE_REPORT,
            File::FILETYPE_SCHEMA,
        ];
        $this->assertEquals($expected, File::getAllFiletypes());
    }

    /**
     * @covers ::setFileType
     * @covers ::getFileType
     */
    public function testSetFileType()
    {
        $this->file->setFileType(File::FILETYPE_SCHEMA);
        $this->assertEquals(File::FILETYPE_SCHEMA, $this->file->getFileType());
    }
}
