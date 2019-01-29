<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Model;

final class SaikuFolder extends AbstractObject
{
    /**
     * @var AbstractObject[]
     */
    private $repoObjects = [];

    /**
     * @return AbstractObject[]
     */
    public function getRepoObjects(): array
    {
        return $this->repoObjects;
    }

    /**
     * @param AbstractObject[] $repoObjects
     */
    public function setRepoObjects(array $repoObjects): void
    {
        $this->repoObjects = $repoObjects;
    }

    protected function hydrate(array $properties): void
    {
        $this->setClass($properties['@class']);
        unset($properties['@class']);

        foreach($properties['repoObjects'] as $objectProperties) {
            $this->repoObjects[] = self::createObject($objectProperties);
        }
        unset($properties['repoObjects']);

        parent::hydrate($properties);
    }

    protected function extract(): array
    {
        $extracted = parent::extract();
        /* @var self $repoObject */
        foreach ($extracted['repoObjects'] as $i => $repoObject) {
            $extracted['repoObjects'][$i] = $repoObject->toArray();
        }

        return $extracted;
    }
}