<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

final class Schema extends AbstractEntity
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $path;
    /** @var string */
    protected $type;
    /** @var string */
    protected $xml;

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : Schema
    {
        $this->name = $name;
        return $this;
    }

    public function getPath() : ?string
    {
        return $this->path;
    }

    public function setPath(string $path) : Schema
    {
        $this->path = $path;
        return $this;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(?string $type) : Schema
    {
        $this->type = $type;
        return $this;
    }

    public function getXml() : ?string
    {
        return $this->xml;
    }

    public function setXml(string $xml) : Schema
    {
        $this->xml = $xml;
        return $this;
    }
}
