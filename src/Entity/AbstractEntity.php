<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

use function array_keys;
use function count;
use function get_object_vars;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

abstract class AbstractEntity
{
    /**
     * @param array|string|null $json
     */
    public function __construct($json = null)
    {
        $properties = $json;
        if (is_string($json)) {
            $properties = json_decode($json, true);
        }
        if (is_array($properties) && count($properties)) {
            $this->hydrate($properties);
        }
    }

    public function __toString() : string
    {
        return json_encode($this->extract());
    }

    public function toArray() : array
    {
        return $this->extract();
    }

    protected function hydrate(array $properties) : void
    {
        $vars = array_keys(get_object_vars($this));
        foreach ($vars as $var) {
            if (isset($properties[$var])) {
                $this->$var = $properties[$var];
            }
        }
    }

    protected function extract() : array
    {
        return get_object_vars($this);
    }
}
