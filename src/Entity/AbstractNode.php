<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

use Kynx\Saiku\Client\Exception\EntityException;

abstract class AbstractNode extends AbstractEntity
{
    const TYPE_FILE = 'FILE';
    const TYPE_FOLDER = 'FOLDER';

    /**
     * @var string
     */
    protected $javaClass;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string[]
     */
    protected $acl = [];

    /**
     * Factory returning `SaikuFile` or `SaikuFolder` based on type
     *
     * @param array|string $json
     *
     * @return AbstractNode
     */
    public static function getInstance($json): AbstractNode
    {
        $properties = $json;
        if (is_string($json)) {
            $properties = json_decode($json, true);
        }
        if (is_array($properties)) {
            $type = $properties['type'] ?? null;
            if ($type == self::TYPE_FILE) {
                return new File($properties);
            } elseif ($type == self::TYPE_FOLDER) {
                return new Folder($properties);
            }
            throw new EntityException(sprintf("Unknown object type '%s'", $type));
        }
        throw new EntityException(sprintf("Cannot create object from %s", gettype($properties)));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getJavaClass(): ?string
    {
        return $this->javaClass;
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

    protected function hydrate(array $properties): void
    {
        $this->javaClass = $properties['@class'] ?? null;
        unset($properties['@class']);
        parent::hydrate($properties);
    }

    protected function extract(): array
    {
        $extracted = parent::extract();
        $extracted['@class'] = $extracted['javaClass'];
        unset($extracted['javaClass']);
        if ($this instanceof Folder) {
            $extracted['type'] = self::TYPE_FOLDER;
        } else {
            $extracted['type'] = self::TYPE_FILE;
        }
        return $extracted;
    }
}
