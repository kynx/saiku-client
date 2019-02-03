<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Kynx\Saiku\Exception\HydrationException;

final class SaikuLicense extends AbstractEntity
{
    /**
     * @var DateTimeImmutable
     */
    protected $expiration;
    /**
     * @var string
     */
    protected $version;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $licenseType;
    /**
     * @var string
     */
    protected $licenseNumber;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $hostname;
    /**
     * @var float
     */
    protected $memoryLimit;

    /**
     * @return DateTimeImmutable
     */
    public function getExpiration(): ?DateTimeImmutable
    {
        return $this->expiration;
    }

    /**
     * @param DateTimeImmutable $expiration
     *
     * @return SaikuLicense
     */
    public function setExpiration(?DateTimeImmutable $expiration): SaikuLicense
    {
        $this->expiration = $expiration;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     *
     * @return SaikuLicense
     */
    public function setVersion(string $version): SaikuLicense
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return SaikuLicense
     */
    public function setEmail(string $email): SaikuLicense
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getLicenseType(): string
    {
        return $this->licenseType;
    }

    /**
     * @param string $licenseType
     *
     * @return SaikuLicense
     */
    public function setLicenseType(string $licenseType): SaikuLicense
    {
        $this->licenseType = $licenseType;
        return $this;
    }

    /**
     * @return string
     */
    public function getLicenseNumber(): string
    {
        return $this->licenseNumber;
    }

    /**
     * @param string $licenseNumber
     *
     * @return SaikuLicense
     */
    public function setLicenseNumber(string $licenseNumber): SaikuLicense
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return SaikuLicense
     */
    public function setName(string $name): SaikuLicense
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     *
     * @return SaikuLicense
     */
    public function setHostname(string $hostname): SaikuLicense
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @return float
     */
    public function getMemoryLimit(): float
    {
        return $this->memoryLimit;
    }

    /**
     * @param float $memoryLimit
     *
     * @return SaikuLicense
     */
    public function setMemoryLimit(float $memoryLimit): SaikuLicense
    {
        $this->memoryLimit = $memoryLimit;
        return $this;
    }

    protected function hydrate(array $properties): void
    {
        if (isset($properties['expiration'])) {
            try {
                $properties['expiration'] = $properties['expiration']
                    ? new DateTimeImmutable($properties['expiration'], new DateTimeZone('UTC'))
                    : null;
            } catch (Exception $e) {
                throw new HydrationException(sprintf(
                    "Could not parse expiration '%s'",
                    $properties['expiration']
                ), $e->getCode(), $e);
            }
        }
        parent::hydrate($properties);
    }

    protected function extract(): array
    {
        $extracted = parent::extract();
        $expiration = $extracted['expiration'];
        if ($expiration instanceof DateTimeImmutable) {
            $extracted['expiration'] = $expiration->format(DateTime::RFC3339);
        }
        return $extracted;
    }
}
