<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Exception;

use DomainException;

final class EntityException extends DomainException implements SaikuExceptionInterface
{
}
