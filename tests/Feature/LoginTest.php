<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;

class LoginTest extends TestCase
{

    /**
     * Test the login with invalid credentials
     *
     * @return void
     */
    public function testLoginInvalidCredentials()
    {
        $this->post($this->apiPath . '/authenticate', [
            'email' => 'invalidacredentials@drypack.com',
            'password' => 'iu33j198uy8'
        ])->assertStatus(401);
    }

    /**
     * Test the login with valid credentials
     *
     * @return void
     */
    public function testLoginValidCredentials()
    {
        $response = $this->post($this->apiPath . '/authenticate', $this->adminUserData);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    /**
     * Test getting the authenticated user data
     *
     * @return void
     */
    public function testGetAuthenticatedUserData()
    {
        $response = $this->get($this->apiPath . '/authenticate/user', $this->createAuthHeaderToAdminUser());

        $response->assertJsonStructure(['user' => [
            'email', 'name', 'roles'
        ]]);
    }
}
