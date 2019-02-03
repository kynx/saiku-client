<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

final class Backup
{
    /**
     * @var DateTimeImmutable
     */
    private $created;
    /**
     * @var SaikuFile
     */
    private $license;
    /**
     * @var SaikuFolder
     */
    private $homes;
    /**
     * @var SaikuAcl[]
     */
    private $acls = [];
    /**
     * @var SaikuUser[]
     */
    private $users = [];
    /**
     * @var SaikuDatasource[]
     */
    private $datasources = [];
    /**
     * @var SaikuSchema[]
     */
    private $schemas = [];

    public function __construct($backup = null)
    {
        if (is_string($backup)) {
            $backup = json_decode($backup, true);
        }
        if (is_array($backup)) {
            $this->created = new DateTimeImmutable($backup['created']);
            if (isset($backup['license'])) {
                $this->license = new SaikuFile($backup['license']);
            }

            $this->homes = new SaikuFolder($backup['homes']);
            foreach ($backup['acls'] as $path => $acl) {
                $this->addAcl($path, new SaikuAcl($acl));
            }
            foreach ($backup['schemas'] as $schema) {
                $this->addSchema(new SaikuSchema($schema));
            }
            foreach ($backup['datasources'] as $datasource) {
                $this->addDatasource(new SaikuDatasource($datasource));
            }
            foreach ($backup['users'] as $user) {
                $this->addUser(new SaikuUser($user));
            }
        } else {
            $this->created = new DateTimeImmutable("now", new DateTimeZone("UTC"));
        }
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return SaikuFile
     */
    public function getLicense(): ?SaikuFile
    {
        return $this->license;
    }

    /**
     * @param SaikuFile $license
     *
     * @return Backup
     */
    public function setLicense(?SaikuFile $license): Backup
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @return SaikuFolder
     */
    public function getHomes(): SaikuFolder
    {
        return $this->homes;
    }

    /**
     * @param SaikuFolder $homes
     *
     * @return Backup
     */
    public function setHomes(SaikuFolder $homes): Backup
    {
        $this->homes = $homes;
        return $this;
    }

    /**
     * @return SaikuAcl[]
     */
    public function getAcl(string $path): SaikuAcl
    {
        return $this->acls[$path];
    }

    public function addAcl(string $path, SaikuAcl $acl)
    {
        $this->acls[$path] = $acl;
    }

    /**
     * @return SaikuUser[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param SaikuUser $user
     */
    public function addUser(SaikuUser $user): void
    {
        $this->users[$user->getUsername()] = $user;
    }

    /**
     * @return SaikuDatasource[]
     */
    public function getDatasources(): array
    {
        return $this->datasources;
    }

    public function addDatasource(SaikuDatasource $datasource): void
    {
        $this->datasources[$datasource->getId()] = $datasource;
    }

    /**
     * @return SaikuSchema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function addSchema(SaikuSchema $schema): void
    {
        $this->schemas[$schema->getName()] = $schema;
    }

    public function toArray(): array
    {
        $data = [
            'created' => $this->created->format(DateTime::RFC3339),
            'license' => $this->license ? $this->license->toArray() : null,
            'users' => [],
            'schemas' => [],
            'datasources' => [],
            'homes' => $this->homes->toArray(),
            'acls' => []
        ];
        foreach ($this->users as $user) {
            $data['users'][] = $user->toArray();
        }
        foreach ($this->schemas as $schema) {
            $data['schemas'][] = $schema->toArray();
        }
        foreach ($this->datasources as $datasource) {
            $data['datasources'][] = $datasource->toArray();
        }
        foreach ($this->acls as $path => $acl) {
            $data['acls'][$path] = $acl->toArray();
        }

        return $data;
    }

    public function toJson(bool $pretty = false): string
    {
        $options = $pretty ? JSON_PRETTY_PRINT : 0;
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }
}
