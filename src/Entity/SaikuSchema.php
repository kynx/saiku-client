<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;

final class SaikuSchema extends AbstractEntity
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
     * @return SaikuSchema
     */
    public function setName(string $name): SaikuSchema
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
     * @return SaikuSchema
     */
    public function setPath(string $path): SaikuSchema
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
     * @return SaikuSchema
     */
    public function setType(?string $type): SaikuSchema
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
     * @return SaikuSchema
     */
    public function setXml(string $xml): SaikuSchema
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
