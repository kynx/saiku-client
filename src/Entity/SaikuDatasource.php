<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;

/**
 * In the UI datasources are displayed in two modes: "simple" and "advanced". When an advanced datasource is saved,
 * most of the other properties are empty.
 *
 * We _could_ parse the advanced string to populate things like username, driver, etc. But for now we're just matching
 * upstream functionality.
 */
final class SaikuDatasource extends AbstractEntity
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $driver;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $schema;
    /**
     * @var string
     */
    protected $connectionname;
    /**
     * @var string
     */
    protected $jdbcurl;
    /**
     * @var string
     */
    protected $connectiontype;
    /**
     * @var string
     */
    protected $advanced;

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return SaikuDatasource
     */
    public function setId(string $id): SaikuDatasource
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     *
     * @return SaikuDatasource
     */
    public function setDriver(string $driver): SaikuDatasource
    {
        $this->driver = $driver;
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
     * @return SaikuDatasource
     */
    public function setPath(string $path): SaikuDatasource
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return SaikuDatasource
     */
    public function setUsername(string $username): SaikuDatasource
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return SaikuDatasource
     */
    public function setPassword(string $password): SaikuDatasource
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * @param string $schema
     *
     * @return SaikuDatasource
     */
    public function setSchema(string $schema): SaikuDatasource
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionname;
    }

    /**
     * @param string $connectionname
     *
     * @return SaikuDatasource
     */
    public function setConnectionName(string $connectionname): SaikuDatasource
    {
        $this->connectionname = $connectionname;
        return $this;
    }

    /**
     * @return string
     */
    public function getJdbcUrl(): ?string
    {
        return $this->jdbcurl;
    }

    /**
     * @param string $jdbcurl
     *
     * @return SaikuDatasource
     */
    public function setJdbcUrl(string $jdbcurl): SaikuDatasource
    {
        $this->jdbcurl = $jdbcurl;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnectionType(): ?string
    {
        return $this->connectiontype;
    }

    /**
     * @param string $connectiontype
     *
     * @return SaikuDatasource
     */
    public function setConnectionType(string $connectiontype): SaikuDatasource
    {
        $this->connectiontype = $connectiontype;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdvanced(): ?string
    {
        return $this->advanced;
    }

    /**
     * @param string $advanced
     *
     * @return SaikuDatasource
     */
    public function setAdvanced(string $advanced): SaikuDatasource
    {
        $this->advanced = $advanced;
        return $this;
    }
}
