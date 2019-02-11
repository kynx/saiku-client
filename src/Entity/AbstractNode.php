<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

use Kynx\Saiku\Client\Exception\EntityException;

use function gettype;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

abstract class AbstractNode extends AbstractEntity
{
    public const TYPE_FILE   = 'FILE';
    public const TYPE_FOLDER = 'FOLDER';

    /** @var string */
    protected $name;
    /** @var string */
    protected $id;
    /** @var string */
    protected $path;
    /** @var string[] */
    protected $acl = [];

    /**
     * Factory returning `SaikuFile` or `SaikuFolder` based on type
     *
     * @param array|string $json
     *
     * @return AbstractNode
     */
    public static function getInstance($json) : AbstractNode
    {
        $properties = $json;
        if (is_string($json)) {
            $properties = json_decode($json, true);
        }
        if (is_array($properties)) {
            $type = $properties['type'] ?? null;
            if ($type === self::TYPE_FILE) {
                return new File($properties);
            } elseif ($type === self::TYPE_FOLDER) {
                return new Folder($properties);
            }
            throw new EntityException(sprintf("Unknown object type '%s'", $type));
        }
        throw new EntityException(sprintf('Cannot create object from %s', gettype($properties)));
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function setPath(string $path) : void
    {
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function getAcl() : array
    {
        return $this->acl;
    }

    /**
     * @param string[] $acl
     */
    public function setAcl(array $acl) : void
    {
        $this->acl = $acl;
    }

    protected function extract() : array
    {
        $extracted = parent::extract();
        if ($this instanceof Folder) {
            $extracted['type'] = self::TYPE_FOLDER;
        } else {
            $extracted['type'] = self::TYPE_FILE;
        }
        return $extracted;
    }
}
