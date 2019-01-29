<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku;

use Kynx\Saiku\Model\AbstractObject;
use Kynx\Saiku\Model\Backup;
use Kynx\Saiku\Model\SaikuFolder;

final class BackupUtil
{
    private $client;

    public function __construct(SaikuClient $client)
    {
        $this->client = $client;
    }

    public function backup(): Backup
    {
        $backup = new Backup();
        $repository = $this->client->getRepository();
        $backup->setRepository($repository);

        foreach ($this->getAcls($repository) as $path => $acl) {
            $backup->addAcl($path, $acl);
        }

        return $backup;
    }

    public function restore(Backup $backup): void
    {

    }

    private function getAcls(AbstractObject $node)
    {
        $path = $node->getPath();
        yield $path => $this->client->getAcl($node->getPath());

        if ($node instanceof SaikuFolder) {
            foreach ($node->getRepoObjects() as $child) {
                foreach ($this->getAcls($child) as $path => $acl) {
                    yield $path => $acl;
                }
            }
        }
    }
}