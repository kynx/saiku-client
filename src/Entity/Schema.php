<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

final class Schema extends AbstractEntity
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $xml;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Schema
     */
    public function setName(string $name): Schema
    {
        $this->name = $name;
        return $this;
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
     *
     * @return Schema
     */
    public function setPath(string $path): Schema
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Schema
     */
    public function setType(?string $type): Schema
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * @param string $xml
     *
     * @return Schema
     */
    public function setXml(string $xml): Schema
    {
        $this->xml = $xml;
        return $this;
    }

    protected function hydrate(array $properties): void
    {
        if (isset($properties['name']) && pathinfo($properties['name'], PATHINFO_EXTENSION) == 'xml') {
            // the name saiku returns includes the ".xml" extension, which screws up saving it again
            $properties['name'] = pathinfo($properties['name'], PATHINFO_FILENAME);
        }
        parent::hydrate($properties);
    }


}
