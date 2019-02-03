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

        foreach ($this->client->getUsers() as $user) {
            $backup->addUser($user);
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
        yield $path => $this->client->getAcl($node->getPath());

        if ($node instanceof SaikuFolder) {
            foreach ($node->getRepoObjects() as $child) {
                foreach ($this->getAcls($child) as $path => $acl) {
                    yield $path => $acl;
                }
            }
        }
    }

    private function restoreUsers(Backup $backup): void
    {
        $existing = [];
        foreach ($this->client->getUsers() as $user) {
            $existing[$user->getUsername()] = $user;
        }
        $restored = [];

        foreach ($backup->getUsers() as $user) {
            $userName = $user->getUsername();
            if (isset($existing[$userName])) {
                $this->client->updateUserAndPassword($user);
            } else {
                $this->client->createUser($user);
            }
            $restored[$userName] = $user;
        }

        foreach (array_diff_key($existing, $restored) as $user) {
            $this->client->deleteUser($user);
        }
    }

    private function restoreNode(AbstractObject $node, Backup $backup): void
    {

    }
}