<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Model;

use Kynx\Saiku\Exception\HydrationException;

abstract class AbstractObject extends AbstractModel
{
    const TYPE_FILE = 'FILE';
    const TYPE_FOLDER = 'FOLDER';

    /**
     * @var string
     */
    private $aClass;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $path;
    /**
     * @var string[]
     */
    private $acl = [];

    /**
     * @param array|string $json
     */
    public static function createObject($json)
    {
        $properties = $json;
        if (is_string($json)) {
            $properties = json_decode($json, true);
        }
        if (is_array($properties)) {
            $type = $properties['type'] ?? null;
            if ($type == self::TYPE_FILE) {
                return new SaikuFile($properties);
            } elseif ($type == self::TYPE_FOLDER) {
                return new SaikuFolder($properties);
            }
            throw new HydrationException(sprintf("Unknown object type '%s'", $type));
        }
        throw new HydrationException(sprintf("Cannot create object from %s", gettype($properties)));
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->aClass;
    }

    /**
     * @param string $aClass
     */
    public function setClass(string $aClass): void
    {
        $this->aClass = $aClass;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function getAcl(): array
    {
        return $this->acl;
    }

    /**
     * @param string[] $acl
     */
    public function setAcl(array $acl): void
    {
        $this->acl = $acl;
    }
}