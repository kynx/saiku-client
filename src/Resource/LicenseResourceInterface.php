<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Kynx\Saiku\Client\Entity\License;
use Psr\Http\Message\StreamInterface;

interface LicenseResourceInterface
{
    /**
     * Returns current license, if it exists
     */
    public function get() : License;

    /**
     * Sets licence
     */
    public function set(StreamInterface $stream) : void;
}
