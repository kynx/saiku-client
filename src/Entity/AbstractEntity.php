<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

abstract class AbstractEntity
{
    /**
     * AbstractModel constructor.
     *
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

    public function __toString(): string
    {
        return json_encode($this->extract());
    }

    public function toArray(): array
    {
        return $this->extract();
    }

    protected function hydrate(array $properties): void
    {
        $vars = array_keys(get_object_vars($this));
        foreach ($vars as $var) {
            if (isset($properties[$var])) {
                $this->$var = $properties[$var];
            }
        }
    }

    protected function extract(): array
    {
        return get_object_vars($this);
    }
}
