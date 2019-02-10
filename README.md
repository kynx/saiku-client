# saiku-client

A PHP Client for [Saiku]'s REST API.

This client works with the Saiku Standalone server. Currently it is tested against version 3.17+, though some 
functionality (notably `$saiku->repository()->get()` with a path argument) relies on PRs currently only merged in their 
`development` branch. I have no idea what would be required to make it work with the Pentaho BI plugin: contributions
welcome.

The primary focus has been on Saiku user CRUD and manipulating ACLs in the repository. Other parts of the [API] are 
exposed - and are used by my [saiku-backup] library - but these are not the core focus.


## Installation

```
composer require kynx/saiku-client
```

## Usage

`Kynx\Saiku\Client\Saiku` is the main entry point for interacting with the API. It must be instantiated with a [Guzzle] 
client configurated with the base URI of your Saiku server and with cookies enabled:

```php
<?php

use GuzzleHttp\Client;
use Kynx\Saiku\Client\Saiku;

$client = new Client(['base_uri' => 'http://localhost:8080/saiku', 'cookies' => true]);
$saiku = new Saiku($client);
$saiku->setUsername('admin')
    ->setPassword('supersecret');
``` 

The client exposes the following methods:

| Method                              | Description                                                                     |
| ----------------------------------- | ------------------------------------------------------------------------------- |
| `$saiku->setUsername($username)`    | Sets name of user accessing API                                                 |
| `$saiku->setPassword($password)`    | Sets password of user accessing API                                             |
| `$saiku->login()`                   | Log in to the API                                                               |
| `$saiku->logout()`                  | Log out from the API                                                            |
| `$saiku->datasource()`              | Returns resource for interacting with datasources                               |
| `$saiku->license()`                 | Returns resource for getting and setting license                                |
| `$saiku->repository()`              | Returns resource for interacting with repository files and folders              |
| `$saiku->schema()`                  | Returns resource for interacting with schemas                                   |
| `$saiku->user()`                    | Returns resource for user CRUD operations                                       |
| `$saiku->proxy($request)`           | Proxies request to saiku, returning response unaltered                          |
| `$saiku->withCookieJar($cookieJar)` | Returns new instance with given cookie jar injected                             |

You do not normally need to call `login()`: if no session is active when you make a request the client will log in 
automatically.

If you are accessing the API across multiple requests, consider using a [custom cookie jar] to avoid repeated logins:

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use Kynx\Saiku\Client\Saiku;

$cookieJar = new SessionCookieJar('saiku', true);
$client = new Client(['base_uri' => 'http://localhost:8080/saiku', 'cookies' => $cookieJar]);
$saiku = new Saiku($client);
```

## Proxying requests

The `proxy()` method takes a PSR-7 [ServerRequestInterface] and passes it to Saiku, logging in if needed. It returns a 
[ResponseInterface] suitable for outputting to the browser. Use this if you want your application to handle requests 
from the Saiku UI but handle authentication itself, without the need for a real SSO integration.

There are a couple of things to keep in mind when you use this. There will be an inevitable performance hit from 
sticking your application between the browser and the Saiku server. And you will will want to use a custom cookie jar to 
avoid repeated logins. But, depending on your setup, the session cookie jar above may not be the best choice.

The Saiku UI is a single-page application. Its JavaScript makes multiple asynchronous requests. By their nature, PHP 
[sessions block] until they are closed: if you use the `SessionCookieJar`, requests from the application will be
processed sequentially, impacting performance. There are a couple of workarounds:

* Implement a different storage mechanism for the cookie. This isn't difficult - see my [expressive-guzzle-cookiejar] 
  for ideas.
* If you are using Memcached for session storage, consider switching off [memcached.sess_locking]. Be aware of the 
  implications on any other session-related activity your application may perform.
* If you're using the Redis session save handler, it doesn't lock by default.
* Keep the sesson open for the shortest possible time. 


## User resource

The user resource enables you to list, create, update and delete users:

| Method                                  | Description                                                                 |
| --------------------------------------- | --------------------------------------------------------------------------- |
| `$saiku->user()->getAll()`              | Returns array of `User` entities                                            |
| `$saiku->user()->get($id)`              | Returns `User` with given id                                                |
| `$saiku->user()->create($user)`         | Creates a new user on Saiku                                                 |
| `$saiku->user()->update($user)`         | Updates user details                                                        |
| `$saiku->user()->updatePassword($user)` | Updates user and password                                                   |
| `$saiku->user()->delete($user)`         | Deletes user                                                                |


## Repository resource

The repository resource enables to you interact with the repository storage:

| Method                                            | Description                                                       |
| ------------------------------------------------- | ----------------------------------------------------------------- |
| `$saiku->repository()->get($path)`                | Returns `Folder` entity at the given path                         |
| `$saiku->repository()->getResource($path)`        | Returns contents of a repository file at path                     |
| `$saiku->repository()->storeResource($resource)`  | Creates / updates a file or folder                                |
| `$saiku->repository()->deleteResource($resource)` | Deletes a file or folder                                          |
| `$saiku->repository()->getAcl($path)`             | Returns `Acl` entity for resource at path                         |
| `$saiku->repository()->setAcl($path, $acl)`       | Sets the ACL for resource at path                                 |


## Exceptions

All exceptions thrown by this library implement `SaikuExceptionInterface`. If in doubt, catch that. Invalid logins will
throw a `BadLoginExcepton`; problems validating entities will throw an `EntityException`; weird responses from Saiku 
(like a 201 when we expected a 200) will throw a `BadResponseException`. Though I've never seen that last one happen.

Almost any other problem - from a requested resource not being found to Saiku barfing blood - will throw a 
`SaikuException`. The API is not terribly RESTful: we get a 500 response and an enormous stack trace back for the vast
majority unexpected operations. If you've got some Java chops and want to make this bit better, please please contribute 
[upstream]. 
 

## Integration tests

The integration tests are not run by default: they need a working Saiku server. Running the integration tests 
**will trash** any existing repository on the server. **Do not** run them against production - or any other Saiku you
care about!

The safest way to run the tests is using the [kynx/saikuce] Docker image. This contains a couple of customisations on 
top of the stock Saiku CE that enable us to use [saiku-backup] to restore state between tests:

```
docker pull kynx/saikuce
docker run --rm -ti -p8080:8080 kynx/saikuce
```

You will need a (free) [evaluation license] from Saiku to run the tests. Unfortunately their licensing server spends
much of its time throwing proxy errors. Be patient - or howl loudly on the [Saiku User Group]. 

To avoid constantly uploading the license to the docker image, copy your license file to `license.lic` in this directory 
and it will be loaded as needed by the test framework. 

If you're not using the docker image, copy [phpunit.xml.dist] to `phpunit.xml` and modify the `SAIKU_*` vars to match 
your environment. Then include the "integration" group when running the tests:

```
vendor/bin/phpunit --group integration
```

As noted, the integration tests rely on [saiku-backup], which in turn depends on this library to restore the backup. If 
it hits problems restoring the backup there will be all kinds of strange failures. Kill the container and start again.



[Saiku]: https://www.meteorite.bi/products/saiku
[API]: https://community.meteorite.bi/docs/
[Guzzle]: http://docs.guzzlephp.org/en/stable/
[custom cookie jar]: http://docs.guzzlephp.org/en/stable/request-options.html#cookies
[ServerRequestInterface]: https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface
[ResponseInterface]: https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface
[sessions block]: https://ma.ttias.be/php-session-locking-prevent-sessions-blocking-in-requests/
[expressive-guzzle-cookiejar]: https://github.com/kynx/expressive-guzzle-cookiejar
[memcached.sess_locking]: http://php.net/manual/en/memcached.configuration.php#ini.memcached.sess-locking
[upstream]: https://github.com/OSBI/saiku
[kynx/saikuce]: https://hub.docker.com/r/kynx/saikuce
[saiku-backup]: https://github.com/kynx/saiku-backup
[evaluation license]: https://licensing.meteorite.bi
[Saiku User Group]: https://groups.google.com/a/saiku.meteorite.bi/forum/#!forum/user
[phpunit.xml.dist]: ./phpunit.xml.dist
