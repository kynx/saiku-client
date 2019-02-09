<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Resource;

use Psr\Http\Message\ResponseInterface;

abstract class AbstractResource
{
    use ExceptionTrait;

    protected $session;

    public function __construct(SessionResource $session)
    {
        $this->session = $session;
    }

    protected function decodeResponse(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}
