<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Exception;

use RuntimeException;

final class LicenseException extends RuntimeException implements SaikuExceptionInterface
{
}
