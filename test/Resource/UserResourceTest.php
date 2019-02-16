<?php

declare(strict_types=1);

/**
 * @copyright: 2019 Matt Kynaston
 * @license  : MIT
 */

namespace KynxTest\Saiku\Client\Resource;

use GuzzleHttp\Psr7\Response;
use Kynx\Saiku\Client\Entity\User;
use Kynx\Saiku\Client\Exception\BadResponseException;
use Kynx\Saiku\Client\Exception\EntityException;
use Kynx\Saiku\Client\Exception\SaikuException;
use Kynx\Saiku\Client\Resource\UserResource;
use KynxTest\Saiku\Client\AbstractTest;

use function json_decode;

/**
 * @coversDefaultClass \Kynx\Saiku\Client\Resource\UserResource
 */
class UserResourceTest extends AbstractTest
{
    /** @var UserResource */
    private $user;

    private $userJson = '{
        "username":"admin",
        "email":"test@admin.com",
        "password":"$2a$10$XbOzOjvpUbLJ26uRWR4bWerATU.HYBOsHqL2LXXSGzMBHO9ui7gbq",
        "roles":["ROLE_USER","ROLE_ADMIN"],
        "id":1
    }';

    protected function setUp()
    {
        parent::setUp();

        $this->user = new UserResource($this->getSessionResource());
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], '[' . $this->userJson . ']'),
        ]);
        $actual = $this->user->getAll();
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(User::class, $actual[0]);
        $request = $this->getLastRequest();
        $this->assertEquals('GET', $request->getMethod());
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->user->getAll();
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll201ThrowsException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(201),
        ]);
        $this->user->getAll();
    }

    /**
     * @covers ::get
     */
    public function testGetReturnsUser()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], $this->userJson),
        ]);

        $actual = $this->user->get(1);
        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals(json_decode($this->userJson, true), $actual->toArray());

        $request = $this->getLastRequest();
        $this->assertStringEndsWith('/1', $request->getUri()->getPath());
    }

    /**
     * @covers ::get
     */
    public function testGetNonexistentUserReturnsEmpty()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $actual = $this->user->get(9999);
        $this->assertNull($actual);
    }

    /**
     * @covers ::get
     */
    public function testGet501ReturnsEmpty()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(501),
        ]);
        $this->user->get(9999);
    }

    /**
     * @covers ::get
     */
    public function testGet404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404),
        ]);
        $this->user->get(9999);
    }

    /**
     * @covers ::get
     */
    public function testGet204ThrowsBadResponseException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(204),
        ]);
        $this->user->get(1);
    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], $this->userJson),
        ]);
        $user   = new User($this->userJson);
        $actual = $this->user->create($user);
        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals($user, $actual);

        $request = $this->getLastRequest();
        $this->assertEquals('POST', $request->getMethod());
    }

    /**
     * @covers ::create
     */
    public function testCreateUnsetsId()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], $this->userJson),
        ]);
        $user = new User($this->userJson);
        $this->user->create($user);
        $expected = json_decode($this->userJson, true);
        unset($expected['id']);

        $request = $this->getLastRequest();
        $this->assertEquals($expected, json_decode((string) $request->getBody(), true));
    }

    /**
     * @covers ::create
     */
    public function testCreate500ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->user->create(new User($this->userJson));
    }

    /**
     * @covers ::update
     */
    public function testUpdate()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], $this->userJson),
        ]);
        $user                 = new User($this->userJson);
        $actual               = $this->user->update($user);
        $request              = $this->getLastRequest();
        $expected             = json_decode($this->userJson, true);
        $expected['password'] = '';

        $this->assertEquals($user, $actual);
        $this->assertNotEmpty($user->getPassword());
        $this->assertEquals($expected, json_decode((string) $request->getBody(), true));
    }

    /**
     * @covers ::updatePassword
     */
    public function testUpdatePassword()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, [], $this->userJson),
        ]);
        $user     = new User($this->userJson);
        $actual   = $this->user->updatePassword($user);
        $request  = $this->getLastRequest();
        $expected = json_decode($this->userJson, true);

        $this->assertEquals($user, $actual);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertStringEndsWith('/' . $user->getUsername(), $request->getUri()->getPath());
        $this->assertEquals($expected, json_decode((string) $request->getBody(), true));
    }

    public function testUpdatePasswordNoIdThrowsException()
    {
        $this->expectException(EntityException::class);
        $this->user->updatePassword(new User());
    }

    public function testUpdatePasswordThrowsNonExistentException()
    {
        $this->expectException(SaikuException::class);
        $this->expectExceptionMessage('Error updating user. Are you sure they exist?');
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->user->updatePassword(new User($this->userJson));
    }

    public function testUpdatePassword404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404),
        ]);
        $this->user->updatePassword(new User($this->userJson));
    }

    /**
     * @covers ::delete
     */
    public function testDelete()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200),
        ]);
        $user = new User($this->userJson);
        $this->user->delete($user);
        $request = $this->getLastRequest();
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/' . $user->getId(), $request->getUri()->getPath());
    }

    /**
     * @covers ::delete
     */
    public function testDeleteNonExistentThrowsNoWobblies()
    {
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(500),
        ]);
        $this->user->delete(new User($this->userJson));
        $this->assertTrue(true);
    }

    /**
     * @covers ::delete
     */
    public function testDelete501ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(501),
        ]);
        $this->user->delete(new User($this->userJson));
    }

    /**
     * @covers ::delete
     */
    public function testDelete404ThrowsException()
    {
        $this->expectException(SaikuException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(404),
        ]);
        $this->user->delete(new User($this->userJson));
    }
}
