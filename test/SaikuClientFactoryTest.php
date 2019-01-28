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
use GuzzleHttp\Cookie\CookieJarInterface;
use Kynx\Saiku\Exception\ContainerException;
use Kynx\Saiku\SaikuClient;
use Kynx\Saiku\SaikuClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class SaikuClientFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var SaikuClientFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->container = new class() implements ContainerInterface {
            public $items = [];
            public $called = [];

            public function get($id)
            {
                $this->called[] = $id;
                if (! isset($this->items[$id])) {
                    throw new class() extends \Exception implements ContainerExceptionInterface {
                    };
                }
                return $this->items[$id];
            }

            public function has($id)
            {
                // not used
            }
        };
        $this->factory = new SaikuClientFactory();
    }

    public function testInvokeReturnsInstance()
    {
        $this->container->items = [
            'config' => [
                'saiku' => [
                    'urls' => [
                        'webapp' => 'http://localhost:9090/saiku',
                    ],
                ],
            ],
        ];

        $instance = $this->factory->__invoke($this->container);
        $this->assertInstanceOf(SaikuClient::class, $instance);
    }

    public function testDefaultCookeJarInjected()
    {
        $this->container->items = [
            'config' => [
                'saiku' => [
                    'urls' => [
                        'webapp' => 'http://localhost:9090/saiku',
                    ],
                ],
            ],
        ];

        $instance = $this->factory->__invoke($this->container);
        $reflection = new \ReflectionClass($instance);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        /* @var Client $client */
        $client = $property->getValue($instance);
        $this->assertInstanceOf(CookieJar::class, $client->getConfig('cookies'));
    }

    public function testCustomCookieJarInjected()
    {
        $jar = $this->prophesize(CookieJarInterface::class);
        $this->container->items = [
            'config' => [
                'saiku' => [
                    'urls' => [
                        'webapp' => 'http://localhost:9090/saiku',
                    ],
                    'cookie_jar' => 'MyCookieJar'
                ],
            ],
            'MyCookieJar' => $jar->reveal()
        ];

        $this->factory->__invoke($this->container);
        $this->assertContains('MyCookieJar', $this->container->called);
    }

    public function testMissingSaikuConfigThrowsException()
    {
        $this->expectException(ContainerException::class);

        $this->container->items = ['config' => []];
        $this->factory->__invoke($this->container);
    }
}
