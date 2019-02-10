<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace KynxTest\Saiku\Client\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Stream;
use Kynx\Saiku\Backup\Entity\Backup;
use Kynx\Saiku\Backup\SaikuRestore;
use Kynx\Saiku\Client\Exception\LicenseException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\SessionResource;
use Kynx\Saiku\Client\Saiku;
use Kynx\Saiku\Client\Exception\SaikuExceptionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractIntegrationTest extends TestCase
{
    /**
     * @var SessionResource
     */
    protected $session;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var CookieJar
     */
    protected $cookieJar;
    /**
     * @var array
     */
    protected $history = [];

    /**
     * Set to `true` to dump request and response history for each request
     * @var bool
     */
    private $dump = false;

    protected function setUp()
    {
        parent::setUp();

        if (! $this->isConfigured()) {
            $this->markTestSkipped("Saiku not configured");
        }

        $this->dump = $GLOBALS['DUMP_HISTORY'] ?? false;
        $this->cookieJar = new CookieJar();
        $history = Middleware::history($this->history);
        $stack = HandlerStack::create();
        $stack->push($history);

        $options = [
            'base_uri' => $GLOBALS['SAIKU_URL'],
            'handler' => $stack,
            'cookies' => $this->cookieJar,
        ];

        $this->client = new Client($options);
        $this->session = new SessionResource($this->client);
        $this->session->setUsername($GLOBALS['SAIKU_USERNAME']);
        $this->session->setPassword($GLOBALS['SAIKU_PASSWORD']);

        $this->restoreBackup();
        $this->history = [];
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->dump) {
            printf("%s:\n", $this->getName());
            foreach ($this->history as $transaction) {
                /* @var RequestInterface $request */
                $request = $transaction['request'];
                $body = (string) $request->getBody();
                printf(
                    "%s %s\n%s\n",
                    $request->getMethod(),
                    $request->getUri(),
                    $body ? $body . "\n" : ""
                );


                if (isset($transaction['response'])) {
                    /* @var ResponseInterface $response */
                    $response = $transaction['response'];
                    $headers = [];
                    foreach ($response->getHeaders() as $name => $header) {
                        $headers[] = $name . ': ' . implode(", ", $header);
                    }

                    printf("Status: %s\n", $response->getStatusCode());
                    printf("%s\n\n%s\n\n", join("\n", $headers), (string) $response->getBody());
                } elseif (isset($transaction['error'])) {
                    printf("Error: %s\n\n", $transaction['error']);
                }
            }
        }
    }

    private function isConfigured()
    {
        return isset($GLOBALS['SAIKU_URL']) && isset($GLOBALS['SAIKU_USERNAME']) && isset($GLOBALS['SAIKU_PASSWORD']);
    }

    private function restoreBackup()
    {
        $saiku = new Saiku($this->client);
        $saiku->setUsername($this->session->getUsername())
            ->setPassword($this->session->getPassword());

        if (! $this->checkLicense($saiku)) {
            $this->markTestSkipped("Error checking license");
        }

        $backup = new Backup(file_get_contents(__DIR__ . '/../asset/backup.json'));
        $restore = new SaikuRestore($saiku);
        try {
            $restore->restore($backup);
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error restoring repository: %s", $e->getMessage()));
        }
    }

    private function checkLicense(Saiku $saiku): bool
    {
        try {
            $saiku->license()->get();
        } catch (LicenseException $e) {
            $this->loadLicense($saiku);
        } catch (SaikuException $e) {
            return false;
        }
        return true;
    }

    private function loadLicense(Saiku $saiku): void
    {
        $file = $this->getLicenseFile();
        $fh = fopen($file, 'r');
        if (! $fh) {
            $this->markTestSkipped(sprintf("Couldn't open '%s' for reading", $file));
        }
        $stream = new Stream($fh);
        try {
            $saiku->license()->set($stream, $GLOBALS['SAIKU_USERNAME'], $GLOBALS['SAIKU_PASSWORD']);
        } catch (SaikuExceptionInterface $e) {
            $this->markTestSkipped(sprintf("Error loading license from '%s: %s", $file, $e->getMessage()));
        } finally {
            fclose($fh);
        }
    }

    protected function getLicenseFile()
    {
        return __DIR__ . '/../../license.lic';
    }
}
