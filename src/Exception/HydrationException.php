<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Exception;

use DomainException;

final class HydrationException extends DomainException implements SaikuExceptionInterface
{
}
