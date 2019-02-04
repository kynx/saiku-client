<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Exception\LicenseException;
use Kynx\Saiku\Exception\SaikuException;
use Kynx\Saiku\Exception\SaikuExceptionInterface;
use Kynx\Saiku\SaikuClient;

trait IntegrationTrait
{
    /**
     * @var CookieJar
     */
    protected $cookieJar;
    protected $history = [];

    protected function getSaiku()
    {
        $this->cookieJar = new CookieJar();
        $history = Middleware::history($this->history);
        $stack = HandlerStack::create();
        $stack->push($history);

        $options = [
            'base_uri' => $GLOBALS['SAIKU_URL'],
            'handler' => $stack,
            'cookies' => $this->cookieJar,
        ];

        $client = new Client($options);
        $saiku = new SaikuClient($client);
        $saiku->setUsername($GLOBALS['SAIKU_USERNAME'])
            ->setPassword($GLOBALS['SAIKU_PASSWORD']);

        return $saiku;
    }

    protected function isConfigured()
    {
        return isset($GLOBALS['SAIKU_URL']) && isset($GLOBALS['SAIKU_USERNAME']) && isset($GLOBALS['SAIKU_PASSWORD']);
    }

    protected function checkLicense(SaikuClient $client): bool
    {
        try {
            $client->getLicense();
        } catch (LicenseException $e) {
            $this->loadLicense($client);
        } catch (SaikuException $e) {
            return false;
        }
        return true;
    }

    protected function loadLicense(SaikuClient $client): void
    {
        $file = $this->getLicenseFile();
        $fh = fopen($file, 'r');
        if (! $fh) {
            $this->markTestSkipped(sprintf("Couldn't open '%s' for reading", $file));
        }
        $stream = new Stream($fh);
        try {
            $client->setLicense($stream);
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error loading license from '%s: %s", $file, $e->getMessage()));
        } finally {
            fclose($fh);
        }
    }

    protected function getLicenseFile()
    {
        return __DIR__ . '/../license.lic';
    }
}
