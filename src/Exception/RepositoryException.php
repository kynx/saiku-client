<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Exception;

use Doctrine\Instantiator\Exception\ExceptionInterface;
use RuntimeException;

final class RepositoryException extends RuntimeException implements ExceptionInterface
{
}
