<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

final class Folder extends AbstractNode
{
    /** @var AbstractNode[] */
    protected $repoObjects = [];

    /**
     * @return AbstractNode[]
     */
    public function getRepoObjects() : array
    {
        return $this->repoObjects;
    }

    /**
     * @param AbstractNode[] $repoObjects
     */
    public function setRepoObjects(array $repoObjects) : void
    {
        $this->repoObjects = $repoObjects;
    }

    protected function hydrate(array $properties) : void
    {
        $repoObjects = $properties['repoObjects'] ?? [];
        foreach ($repoObjects as $objectProperties) {
            $this->repoObjects[] = self::getInstance($objectProperties);
        }
        unset($properties['repoObjects']);

        parent::hydrate($properties);
    }

    protected function extract() : array
    {
        $extracted = parent::extract();

        /** @var self $repoObject */
        foreach ($extracted['repoObjects'] as $i => $repoObject) {
            $extracted['repoObjects'][$i] = $repoObject->toArray();
        }

        return $extracted;
    }
}
