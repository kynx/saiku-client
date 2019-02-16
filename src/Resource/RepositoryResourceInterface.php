<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\Acl;

interface RepositoryResourceInterface
{
    /**
     * Returns file or folder from repository
     */
    public function get(?string $path = null, bool $contents = false, ?array $types = null) : AbstractNode;

    /**
     * Returns content of repository resource
     */
    public function getResource(string $path) : string;

    /**
     * Stores resource in repository
     */
    public function storeResource(AbstractNode $resource) : void;

    /**
     * Deletes resource from repository
     */
    public function deleteResource(AbstractNode $resource) : void;

    /**
     * Returns ACL for node at `$path`
     */
    public function getAcl(string $path) : Acl;

    /**
     * Sets ACL for node at `$path`
     */
    public function setAcl(string $path, Acl $acl) : void;
}
