<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

/**
 * In the UI datasources are displayed in two modes: "simple" and "advanced". When an advanced datasource is saved,
 * most of the other properties are empty.
 *
 * We _could_ parse the advanced string to populate things like username, driver, etc. But for now we're just matching
 * upstream functionality.
 */
final class Datasource extends AbstractEntity
{
    /** @var string */
    protected $id;
    /** @var string */
    protected $driver;
    /** @var string */
    protected $path;
    /** @var string */
    protected $username;
    /** @var string */
    protected $password;
    /** @var string */
    protected $schema;
    /** @var string */
    protected $connectionname;
    /** @var string */
    protected $jdbcurl;
    /** @var string */
    protected $connectiontype;
    /** @var string */
    protected $advanced;

    public function getId() : ?string
    {
        return $this->id;
    }

    public function getDriver() : ?string
    {
        return $this->driver;
    }

    public function setDriver(?string $driver) : Datasource
    {
        $this->driver = $driver;
        return $this;
    }

    public function getPath() : ?string
    {
        return $this->path;
    }

    public function setPath(?string $path) : Datasource
    {
        $this->path = $path;
        return $this;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username) : Datasource
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword() : ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password) : Datasource
    {
        $this->password = $password;
        return $this;
    }

    public function getSchema() : ?string
    {
        return $this->schema;
    }

    public function setSchema(?string $schema) : Datasource
    {
        $this->schema = $schema;
        return $this;
    }

    public function getConnectionName() : ?string
    {
        return $this->connectionname;
    }

    public function setConnectionName(?string $connectionname) : Datasource
    {
        $this->connectionname = $connectionname;
        return $this;
    }

    public function getJdbcUrl() : ?string
    {
        return $this->jdbcurl;
    }

    public function setJdbcUrl(?string $jdbcurl) : Datasource
    {
        $this->jdbcurl = $jdbcurl;
        return $this;
    }

    public function getConnectionType() : ?string
    {
        return $this->connectiontype;
    }

    public function setConnectionType(?string $connectiontype) : Datasource
    {
        $this->connectiontype = $connectiontype;
        return $this;
    }

    public function getAdvanced() : ?string
    {
        return $this->advanced;
    }

    public function setAdvanced(string $advanced) : Datasource
    {
        $this->advanced = $advanced;
        return $this;
    }

    protected function hydrate(array $properties) : void
    {
        // @todo Report upstream
        // For some reason we get "null" as a string value here :|
        foreach ($properties as $name => $value) {
            if ($value === 'null') {
                $properties[$name] = null;
            }
        }
        parent::hydrate($properties);
    }
}
