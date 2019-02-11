<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Exception;

use RuntimeException;

final class BadLoginException extends RuntimeException implements SaikuExceptionInterface
{
}
