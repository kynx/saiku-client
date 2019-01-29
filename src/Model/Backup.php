<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Model;

final class Backup
{
    /**
     * @var SaikuFolder
     */
    private $repository;
    /**
     * @var SaikuAcl[]
     */
    private $acls = [];

    public function __construct($backup = null)
    {
        if (is_string($backup)) {
            $backup = json_decode($backup, true);
        }
        if (is_array($backup)) {
            $this->repository = new SaikuFolder($backup['repository'] ?? null);
            foreach ($backup['acls'] as $path => $acl) {
                $this->addAcl($path, new SaikuAcl($acl));
            }
        }
    }

    /**
     * @return SaikuFolder
     */
    public function getRepository(): SaikuFolder
    {
        return $this->repository;
    }

    /**
     * @param SaikuFolder $repository
     *
     * @return Backup
     */
    public function setRepository(SaikuFolder $repository): Backup
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return SaikuAcl[]
     */
    public function getAcl(string $path): SaikuAcl
    {
        return $this->acls;
    }

    public function addAcl(string $path, SaikuAcl $acl)
    {
        $this->acls[$path] = $acl;
    }
}