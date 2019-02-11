<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class BadResponseException extends RuntimeException implements SaikuExceptionInterface
{
    private $response;

    public function __construct(string $message = '', ?ResponseInterface $response = null, ?Throwable $previous = null)
    {
        $this->response = $response;
        $code           = $response ? $response->getStatusCode() : 500;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse() : ResponseInterface
    {
        return $this->response;
    }
}
