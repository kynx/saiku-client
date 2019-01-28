# saiku-client

A PHP Client for [Saiku]'s REST API.

This is a work in progress. Right now only login / logout are well tested.

The initial goal is to allow creating, deleting and updating users from PHP and to provide a mechanism for proxying
requests from Saiku's UI to the server, handling authentication from the PHP application.

Once those tests are passing work will start on listing repository contents and modifying ACLs.

## Integration tests

The integration tests are not run by default. To run them you will need a working Saiku server. The [buggtb/saikuce]
docker image makes this easy:

```
docker pull buggtb/saikuce
docker run --rm -ti -p8080:8080 buggtb/saikuce
```

Copy [phpunit.xml.dist] to `phpunit.xml` and modify the `SAIKU_*` vars to match your environment. Then include the
"integration" group when running the tests:

```
vendor/bin/phpunit --group integration
```

*Note*: the integration tests overwrite the existing repository. Do *not* run them against a production server.


[Saiku]: https://www.meteorite.bi/products/saiku
[buggtb/saikuce]: https://hub.docker.com/r/buggtb/saikuce
[phpunit.xml.dist]: ./phpunit.xml.dist
