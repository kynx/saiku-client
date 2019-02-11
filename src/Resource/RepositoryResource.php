<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Kynx\Saiku\Client\Entity\AbstractNode;
use Kynx\Saiku\Client\Entity\Acl;
use Kynx\Saiku\Client\Entity\File;
use Kynx\Saiku\Client\Entity\Folder;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\SaikuException;

use function count;
use function implode;
use function in_array;
use function json_encode;
use function sprintf;

final class RepositoryResource extends AbstractResource
{
    public const PATH          = 'rest/saiku/api/repository/';
    public const PATH_ACL      = 'rest/saiku/api/repository/resource/acl/';
    public const PATH_RESOURCE = 'rest/saiku/api/repository/resource/';

    /**
     * Returns file or folder from repository
     *
     * @see https://github.com/OSBI/saiku/pull/690
     */
    public function get(?string $path = null, bool $contents = false, ?array $types = null) : AbstractNode
    {
        try {
            if ($types === null) {
                $types = File::getAllFiletypes();
            }
            $query    = [
                'path' => $path,
                'type' => implode(',', $types),
            ];
            $response = $this->session->request('GET', self::PATH, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            $decoded = $this->decodeResponse($response);
            if ($path && count($decoded) === 1) {
                $type = $decoded[0]['type'] ?? AbstractNode::TYPE_FOLDER;
                $node = $type === AbstractNode::TYPE_FOLDER ? new Folder($decoded[0]) : new File($decoded[0]);
            } else {
                $node = new Folder(['path' => $path ?? '/', 'repoObjects' => $decoded]);
            }
            if ($contents) {
                $this->populateFolderContents($node);
            }
            return $node;
        }

        throw new BadResponseException(sprintf(
            "Couldn't get repository: %s",
            (string) $response->getBody()
        ), $response);
    }

    public function getResource(string $path) : string
    {
        try {
            $query    = ['file' => $path];
            $response = $this->session->request('GET', self::PATH_RESOURCE, ['query' => $query]);
        } catch (ServerException $e) {
            // @todo Report upstream
            // Saiku throws a 500 error when the resource does not exist :(
            throw new SaikuException(sprintf("Error getting '%s'. Are you sure it exists?", $path), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            return (string) $response->getBody();
        }

        throw new BadResponseException(sprintf(
            "Couldn't get resource at path '%s': %s",
            $path,
            (string) $response->getBody()
        ), $response);
    }

    public function storeResource(AbstractNode $resource) : void
    {
        $params = ['file' => $resource->getPath()];
        if ($resource instanceof File) {
            $params['content'] = $resource->getContent();
        }

        try {
            $this->session->request('POST', self::PATH_RESOURCE, ['form_params' => $params]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteResource(AbstractNode $resource) : void
    {
        $query = ['file' => $resource->getPath()];
        try {
            $this->session->request('DELETE', self::PATH_RESOURCE, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns ACL for node at `$path`, or `null` if none set
     */
    public function getAcl(string $path) : ?Acl
    {
        $query = ['file' => $path];
        try {
            $response = $this->session->request('GET', self::PATH_ACL, ['query' => $query]);
        } catch (GuzzleException $e) {
            // @todo Report upstream
            // phpcs:disable
            // non-existent paths throw 500 with "You dont have permission to retrieve ACL for file: /homes/home:admin/nothere.saiku"
            // phpcs:enable
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() === 200) {
            $item = $this->decodeResponse($response);
            if (isset($item['type'])) {
                return new Acl($item);
            }
            return null;
        }
        throw new BadResponseException(sprintf("Couldn't get ACL at path '%s'", $path), $response);
    }

    public function setAcl(string $path, Acl $acl) : void
    {
        $params = [
            'file' => $path,
            'acl'  => json_encode($acl->toArray()),
        ];

        try {
            $this->session->request('POST', self::PATH_ACL, ['form_params' => $params]);
        } catch (GuzzleException $e) {
            // @todo Report upstream
            // this doesn't appear to throw _any_ exceptions if the Acl is malformed!
            throw new SaikuException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function populateFolderContents(Folder $folder) : void
    {
        $contentType = [File::FILETYPE_LICENSE, File::FILETYPE_REPORT];
        foreach ($folder->getRepoObjects() as $object) {
            if ($object instanceof Folder) {
                $this->populateFolderContents($object);
            } elseif ($object instanceof File && in_array($object->getFileType(), $contentType)) {
                $object->setContent($this->getResource($object->getPath()));
            }
        }
    }
}
