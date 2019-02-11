<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

final class File extends AbstractNode
{
    public const FILETYPE_DATASOURCE = 'sds';
    public const FILETYPE_LICENSE    = 'lic';
    public const FILETYPE_REPORT     = 'saiku';
    public const FILETYPE_SCHEMA     = 'xml';

    /** @var string */
    protected $fileType;
    /** @var string */
    protected $content;

    public function getFileType() : string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType) : void
    {
        $this->fileType = $fileType;
    }

    public function getContent() : ?string
    {
        return $this->content;
    }

    public function setContent(string $content) : File
    {
        $this->content = $content;
        return $this;
    }

    public static function getAllFiletypes() : array
    {
        return [
            self::FILETYPE_DATASOURCE,
            self::FILETYPE_LICENSE,
            self::FILETYPE_REPORT,
            self::FILETYPE_SCHEMA,
        ];
    }
}
