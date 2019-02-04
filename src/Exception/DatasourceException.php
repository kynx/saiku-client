<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Exception;

use Doctrine\Instantiator\Exception\ExceptionInterface;
use RuntimeException;

final class DatasourceException extends RuntimeException implements ExceptionInterface
{
}