<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\User;

class AuthenticateTest extends TestCase
{

    /**
     * Test api to send reset password mail
     */
    public function testApiAuthentication()
    {
        $response = $this->post($this->apiPath . '/authenticate', $this->basicUserData);
        $response->assertStatus(200);
        $responseData = $response->json();
        $response->assertJsonStructure(['token']);
    }

    /**
     * Test api to send reset password mail
     */
    public function testApiGetAuthenticatedUser()
    {
        $response = $this->get($this->apiPath . '/authenticate/user', $this->createAuthHeaderToBasicUser());
        $response->assertStatus(200);
        $responseData = $response->json();
        $response->assertJsonStructure([
            '*' => $this->userJsonStructure()
        ]);
    }

    /**
     * Return the default user json structure
     */
    private function userJsonStructure(){
        return [
            'id',
            'name',
            'roles'=>[
                '*'=>[
                    'title',
                    'slug'
                ]
            ],
            'allowed_actions' => [
                '*' => [
                    'id',
                    'action_type_slug',
                    'resource_slug',
                    'action_type_name',
                    'dependencies',
                ]
            ]
        ];
    }
}
