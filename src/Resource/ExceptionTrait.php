<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Resource;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Kynx\Saiku\Client\Exception\BadLoginException;
use Kynx\Saiku\Client\Exception\LicenseException;

use function sprintf;
use function stristr;

trait ExceptionTrait
{
    protected function isUnauthorisedException(GuzzleException $exception) : bool
    {
        if ($exception instanceof ServerException) {
            // invalid login returns a 500 status :|
            return $exception->getResponse()->getStatusCode() === 500
                && stristr($exception->getMessage(), 'authentication failed');
        } elseif ($exception instanceof ClientException) {
            return $exception->getResponse()->getStatusCode() === 401;
        }
        return false;
    }

    protected function isLicenseException(GuzzleException $exception) : bool
    {
        if ($exception instanceof ServerException && $exception->getCode() === 500) {
            $body = (string) $exception->getResponse()->getBody();
            return (bool) stristr($body, 'error fetching license');
        }
        return false;
    }

    protected function throwBadLoginException(GuzzleException $exception) : void
    {
        throw new BadLoginException(sprintf("Couldn't get session for '%s'", $this->username), 401, $exception);
    }

    protected function throwLicenseException(GuzzleException $exception) : void
    {
        throw new LicenseException('Invalid license', $exception->getCode(), $exception);
    }
}
