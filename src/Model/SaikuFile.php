<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Model;

final class SaikuFile extends AbstractObject
{
    const FILETYPE_DATASOURCE = 'sds';
    const FILETYPE_LICENSE = 'lic';
    const FILETYPE_REPORT = 'saiku';
    const FILETYPE_SCHEMA = 'xml';

    /**
     * @var string
     */
    private $fileType;

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
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
