<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\User;

class PasswordTest extends TestCase
{
    /**
     * Test api send reset token
     */
    public function testApiUserShouldSendResetPasswordEmail()
    {
        $user = factory(\App\User::class)->create();
        $response = $this->post($this->apiPath . '/password/email', collect($user)->only('email')->toArray());
        $response->assertStatus(200);

    }

    /**
     * Test api to send save reset password with invalid token
     * We can not test the case save success, bacause we would need a valid token
     */
    public function testApiUserShouldntSavePasswordWithInvalidToken()
    {
        $user = factory(\App\User::class)->create();
        $password = $this->faker->password;
        $data = [
            'email'=> $user->email,
            'password'=>$password,
            'password_confirmation'=>$password,
            'token'=>'invalid-token'
        ];
        $response = $this->post($this->apiPath . '/password/reset', $data);
        $response->assertStatus(500);
        $response->assertJsonStructure(['error']);

    }
}
