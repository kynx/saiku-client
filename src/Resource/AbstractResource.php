<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use Psr\Http\Message\ResponseInterface;

use function json_decode;

abstract class AbstractResource
{
    use ExceptionTrait;

    protected $session;

    public function __construct(SessionResource $session)
    {
        $this->session = $session;
    }

    protected function decodeResponse(ResponseInterface $response) : array
    {
        return json_decode((string) $response->getBody(), true);
    }
}
