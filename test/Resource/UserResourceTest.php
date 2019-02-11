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

    protected function setUp()
    {
        parent::setUp();

        $this->user = new UserResource($this->getSessionResource());
    }

    /**
     * @covers ::get
     */
    public function testGetReturnsUser()
    {
        $user = '{
            "username":"admin",
            "email":"test@admin.com",
            "password":"$2a$10$XbOzOjvpUbLJ26uRWR4bWerATU.HYBOsHqL2LXXSGzMBHO9ui7gbq",
            "roles":["ROLE_USER","ROLE_ADMIN"],
            "id":1
        }';
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(200, ['Content-Type' => 'application/json'], $user),
        ]);

        $actual = $this->user->get(1);
        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals(json_decode($user, true), $actual->toArray());
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
    public function testGet204ThrowsBadResponseException()
    {
        $this->expectException(BadResponseException::class);
        $this->mockResponses([
            $this->getLoginSuccessResponse(),
            new Response(204),
        ]);
        $this->user->get(1);
    }
}
