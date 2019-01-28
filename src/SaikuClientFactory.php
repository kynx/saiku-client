<?php
/**
 * @author   : matt@kynx.org
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */
declare(strict_types=1);

namespace Kynx\Saiku;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Kynx\Saiku\Exception\ContainerException;
use Psr\Container\ContainerInterface;

final class SaikuClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $saiku = isset($config['saiku']) ? $config['saiku'] : [];
        if (empty($saiku)) {
            throw new ContainerException("Missing [saiku] configuration");
        }

        if ($saiku['cookie_jar']) {
            $cookieJar = $container->get($saiku['cookie_jar']);
        } else {
            $cookieJar = new CookieJar();
        }

        $options = [
            'base_uri' => $saiku['urls']['webapp'],
            'cookies' => $cookieJar,
        ];

        $client = new Client($options);
        return new SaikuClient($client);
    }
}
