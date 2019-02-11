<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace Kynx\Saiku\Client\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Kynx\Saiku\Client\Exception\EntityException;
use Throwable;

use function sprintf;

final class License extends AbstractEntity
{
    /** @var DateTimeImmutable */
    protected $expiration;
    /** @var string */
    protected $version;
    /** @var string */
    protected $email;
    /** @var string */
    protected $licenseType;
    /** @var string */
    protected $licenseNumber;
    /** @var string */
    protected $name;
    /** @var string */
    protected $hostname;
    /** @var float */
    protected $memoryLimit;

    public function getExpiration() : ?DateTimeImmutable
    {
        return $this->expiration;
    }

    public function setExpiration(?DateTimeImmutable $expiration) : License
    {
        $this->expiration = $expiration;
        return $this;
    }

    public function getVersion() : string
    {
        return $this->version;
    }

    public function setVersion(string $version) : License
    {
        $this->version = $version;
        return $this;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function setEmail(string $email) : License
    {
        $this->email = $email;
        return $this;
    }

    public function getLicenseType() : string
    {
        return $this->licenseType;
    }

    public function setLicenseType(string $licenseType) : License
    {
        $this->licenseType = $licenseType;
        return $this;
    }

    public function getLicenseNumber() : string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber) : License
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : License
    {
        $this->name = $name;
        return $this;
    }

    public function getHostname() : string
    {
        return $this->hostname;
    }

    public function setHostname(string $hostname) : License
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function getMemoryLimit() : float
    {
        return $this->memoryLimit;
    }

    public function setMemoryLimit(float $memoryLimit) : License
    {
        $this->memoryLimit = $memoryLimit;
        return $this;
    }

    protected function hydrate(array $properties) : void
    {
        if (isset($properties['expiration'])) {
            try {
                $properties['expiration'] = $properties['expiration']
                    ? new DateTimeImmutable($properties['expiration'], new DateTimeZone('UTC'))
                    : null;
            } catch (Throwable $e) {
                throw new EntityException(sprintf(
                    "Could not parse expiration '%s'",
                    $properties['expiration']
                ), $e->getCode(), $e);
            }
        }
        parent::hydrate($properties);
    }

    protected function extract() : array
    {
        $extracted  = parent::extract();
        $expiration = $extracted['expiration'];
        if ($expiration instanceof DateTimeImmutable) {
            $extracted['expiration'] = $expiration->format(DateTime::RFC3339);
        }
        return $extracted;
    }
}
