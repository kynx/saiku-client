<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

final class File extends AbstractNode
{
    const FILETYPE_DATASOURCE = 'sds';
    const FILETYPE_LICENSE = 'lic';
    const FILETYPE_REPORT = 'saiku';
    const FILETYPE_SCHEMA = 'xml';

    /**
     * @var string
     */
    protected $fileType;
    /**
     * @var string
     */
    protected $content;

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return File
     */
    public function setContent(string $content): File
    {
        $this->content = $content;
        return $this;
    }

    public static function getAllFiletypes(): array
    {
        return [
            self::FILETYPE_DATASOURCE,
            self::FILETYPE_LICENSE,
            self::FILETYPE_REPORT,
            self::FILETYPE_SCHEMA
        ];
    }
}
