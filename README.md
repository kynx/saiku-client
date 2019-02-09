# saiku-client

A PHP Client for [Saiku]'s REST API.

This is a work in progress. Right now only login / logout are well tested.

The initial goal is to allow creating, deleting and updating users from PHP and to provide a mechanism for proxying
requests from Saiku's UI to the server, handling authentication from the PHP application.

Once those tests are passing work will start on listing repository contents and modifying ACLs.

## Installation

```
composer require kynx/saiku-client
```

## Usage

`Kynx\Saiku\Client\SaikuClient` is the main entry point for interacting with the API. It must be instantiated with a 
[Guzzle] client configurated with the base URI of your Saiku server and with cookies enabled:

```php
<?php
use GuzzleHttp\Client;
use Kynx\Saiku\Client\SaikuClient;

$client = new Client(['base_uri' => 'http://localhost:8080/saiku', 'cookies' => true]);
$saiku = new SaikuClient($client);
$saiku->setUsername('admin')
    ->setPassword('supersecret');
``` 

The client provides the following methods:

| Method                           | Description                                            |
| -------------------------------- | ------------------------------------------------------ |
| `$saiku->setUsername($username)` | Sets name of user accessing API                        |
| `$saiku->setPassword($password)` | Sets password of user accessing API                    |
| `$saiku->login()`                | Log in to the API                                      |
| `$saiku->logout()`               | Log out from the API                                   |
| `$saiku->datasource()`           | Returns datasource resource                            |
| `$saiku->license()`              | Returns license resource                               |
| `$saiku->repository()`           | Returns repository resource                            |
| `$saiku->schema()`               | Returns schema resource                                |
| `$saiku->user()`                 | Returns user resource                                  |
| `$saiku->proxy($request)`        | Proxies request to saiku, returning response unaltered |
| `$saiku->withCookieJar()`        | Returns new instance with given cookie jar injected    |

You do not normally need to call `login()`: if no session is active when you make a request the client will log in 
automatically.

If you are accessing the API across multiple requests, consider using a custom cookie jar to avoid repeated logins:

```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use Kynx\Saiku\Client\SaikuClient;

$cookieJar = new SessionCookieJar('saiku', true);
$client = new Client(['base_uri' => 'http://localhost:8080/saiku', 'cookies' => $cookieJar]);
$saiku = new SaikuClient($client);
```

## User resource

The user resource enables you to list, create, update and delete users:

| Method                                  | Description                      |
| --------------------------------------- | -------------------------------- |
| `$saiku->user()->getAll()`              | Returns array of `User` entities |
| `$saiku->user()->get($id)`              | Returns `User` with given id     |
| `$saiku->user()->create($user)`         | Creates a new user on Saiku      |
| `$saiku->user()->update($user)`         | Updates user details             |
| `$saiku->user()->updatePassword($user)` | Updates user and password        |
| `$saiku->user()->delete($user)`         | Deletes user                     |


## Repository resource

The repository resource enables to you interact with the repository where reports etc are stored:

| Method                                            | Description                                          |
| ------------------------------------------------- | ---------------------------------------------------- |
| `$saiku->repository()->get()`                     | Returns `Folder` entity containing entire repository |
| `$saiku->repository()->getResource($path)`        | Returns contents of a repository file at path        |
| `$saiku->repository()->storeResource($resource)`  | Creates / updates a file or folder                   |
| `$saiku->repository()->deleteResource($resource)` | Deletes a file or folder                             |
| `$saiku->repository()->getAcl($path)`             | Returns `Acl` entity for resource at path            |
| `$saiku->repository()->setAcl($path, $acl)`       | Sets the ACL for resource at path                    |


## Integration tests

The integration tests are not run by default: they need a working Saiku server. Running the integration tests 
**will trash** any existing repository on the server. **Do not** run them against production - or any other Saiku you
care about!

The safest way to run the tests is using the [kynx/saikuce] docker image:

```
docker pull kynx/saikuce
docker run --rm -ti -p8080:8080 kynx/saikuce
```

You will need a (free) [evaluation license] from Saiku to run the tests. Unfortunately their licensing server spends
much of its time throwing proxy errors. Be patient - or howl loudly on the [Saiku User Group]. 

To avoid constantly uploading the license to the docker image, copy your license file to `license.lic` in this directory 
and it will be loaded as needed by the test framework. 

Now copy [phpunit.xml.dist] to `phpunit.xml` and modify the `SAIKU_*` vars to match your environment. Then include the
"integration" group when running the tests:

```
vendor/bin/phpunit --group integration
```




[Saiku]: https://www.meteorite.bi/products/saiku
[kynx/saikuce]: https://hub.docker.com/r/kynx/saikuce
[evaluation license]: https://licensing.meteorite.bi
[Saiku User Group]: https://groups.google.com/a/saiku.meteorite.bi/forum/#!forum/user
[phpunit.xml.dist]: ./phpunit.xml.dist
