<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Exception;

use DomainException;

final class EntityException extends DomainException implements SaikuExceptionInterface
{
}
