<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;


trait HomesTrait
{
    protected function getHomes(SaikuFolder $repository): ?SaikuFolder
    {
        foreach ($repository->getRepoObjects() as $object) {
            if ($object instanceof SaikuFolder and $object->getPath() == '/homes') {
                return $object;
            }
        }
        return null;
    }
}
