<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku;

use Kynx\Saiku\Entity\AbstractNode;
use Kynx\Saiku\Entity\Backup;
use Kynx\Saiku\Entity\HomesTrait;
use Kynx\Saiku\Entity\SaikuAcl;
use Kynx\Saiku\Entity\SaikuFile;
use Kynx\Saiku\Entity\SaikuFolder;

final class SaikuBackup
{
    use HomesTrait;

    private $client;
    private $includeLicense;

    public function __construct(SaikuClient $client, bool $includeLicense = false)
    {
        $this->client = $client;
        $this->includeLicense = $includeLicense;
    }

    public function backup(): Backup
    {
        $backup = new Backup();

        $repository = $this->client->getRespository(true);

        if ($this->includeLicense) {
            $backup->setLicense($this->getLicense($repository));
        }

        $homes = $this->getHomes($repository);
        if ($homes instanceof SaikuFolder) {
            $backup->setHomes($homes);
            foreach ($this->getAcls($homes) as $path => $acl) {
                $backup->addAcl($path, $acl);
            }
        }

        foreach ($this->client->getUsers() as $user) {
            $backup->addUser($user);
        }

        foreach ($this->client->getSchemas(true) as $schema) {
            $backup->addSchema($schema);
        }

        foreach ($this->client->getDatasources() as $datasource) {
            $backup->addDatasource($datasource);
        }

        return $backup;
    }

    /**
     * @param AbstractNode $node
     *
     * @return \Generator|SaikuAcl[]
     */
    private function getAcls(AbstractNode $node)
    {
        $path = $node->getPath();
        $acl = $this->client->getAcl($node->getPath());
        if ($acl !== null) {
            yield $path => $this->client->getAcl($node->getPath());
        }

        if ($node instanceof SaikuFolder) {
            foreach ($node->getRepoObjects() as $child) {
                foreach ($this->getAcls($child) as $path => $acl) {
                    if ($acl !== null) {
                        yield $path => $this->client->getAcl($node->getPath());
                    }
                }
            }
        }
    }

    private function getLicense(SaikuFolder $repository): ?SaikuFile
    {
        foreach ($repository->getRepoObjects() as $object) {
            if ($object instanceof SaikuFile && $object->getFileType() == SaikuFile::FILETYPE_LICENSE) {
                return $object;
            }
            if ($object instanceof SaikuFolder) {
                $license = $this->getLicense($object);
                if ($license !== null) {
                    return $license;
                }
            }
        }
        return null;
    }
}
