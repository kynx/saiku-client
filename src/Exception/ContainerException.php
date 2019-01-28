<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Exception;

use Psr\Container\ContainerExceptionInterface;

final class ContainerException extends \RuntimeException implements ContainerExceptionInterface, SaikuExceptionInterface
{
}
