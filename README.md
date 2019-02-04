# saiku-client

A PHP Client for [Saiku]'s REST API.

This is a work in progress. Right now only login / logout are well tested.

The initial goal is to allow creating, deleting and updating users from PHP and to provide a mechanism for proxying
requests from Saiku's UI to the server, handling authentication from the PHP application.

Once those tests are passing work will start on listing repository contents and modifying ACLs.

## Integration tests

The integration tests are not run by default: they need a working Saiku server. Running the integration tests 
**will trash** any existing repository on the server. **Do not** run them against production - or any other Saiku you
care about!

The safest way to run the tests is using [buggtb/saikuce]'s docker image:

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
[buggtb/saikuce]: https://hub.docker.com/r/buggtb/saikuce
[evaluation license]: https://licensing.meteorite.bi
[Saiku User Group]: https://groups.google.com/a/saiku.meteorite.bi/forum/#!forum/user
[phpunit.xml.dist]: ./phpunit.xml.dist
