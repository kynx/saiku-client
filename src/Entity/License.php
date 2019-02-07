<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku\Client\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Kynx\Saiku\Client\Exception\EntityException;

final class License extends AbstractEntity
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
     * @return License
     */
    public function setExpiration(?DateTimeImmutable $expiration): License
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
     * @return License
     */
    public function setVersion(string $version): License
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
     * @return License
     */
    public function setEmail(string $email): License
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
     * @return License
     */
    public function setLicenseType(string $licenseType): License
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
     * @return License
     */
    public function setLicenseNumber(string $licenseNumber): License
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
     * @return License
     */
    public function setName(string $name): License
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
     * @return License
     */
    public function setHostname(string $hostname): License
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
     * @return License
     */
    public function setMemoryLimit(float $memoryLimit): License
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
                throw new EntityException(sprintf(
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
