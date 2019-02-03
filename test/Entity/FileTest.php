<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Entity;

use Kynx\Saiku\Entity\File;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Kynx\Saiku\Entity\File
 */
class FileTest extends TestCase
{
    /**
     * @var File
     */
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
        $this->file->setContent("some content");
        $this->assertEquals("some content", $this->file->getContent());
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
            File::FILETYPE_SCHEMA
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
