<?php
/**
 * @copyright: 2019 Matt Kynaston <matt@kynx.org>
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Exception;

use Doctrine\Instantiator\Exception\ExceptionInterface;
use RuntimeException;

final class NotFoundException extends RuntimeException implements ExceptionInterface
{
}
