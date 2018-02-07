<?php

namespace Tests\Unit;

use Tests\TestCase;

class AuditTest extends TestCase
{
    /**
     * Test api load models
     */
    public function testApiLoadModels()
    {
        $response = $this->get($this->apiPath . '/audit/models', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);

        $response->assertJsonStructure(['models']);
        // Need to be at least user model
        $this->assertContains('User', $response->json()['models']);
    }

    /**
     * Test api search
     */
    public function testApiSearch()
    {
        $header = $this->createAuthHeaderToAdminUser();

        // Create and update a user to generate audit data
        $user = factory(\App\User::class)->states('rest')->make();
        $response = $this->post($this->apiPath . '/users', $user->getAttributes(), $header);

        $createdUser = $response->json();
        $createdUser['name'] = 'New name AuditTest';
        $createdUser['email'] = '9u3h8912n3y82t37812yh3y812gt3@873189y371.com';

        $response = $this->put(
            $this->apiPath . '/users/' . $createdUser['id'],
            $createdUser,
            $header
        );

        $date = \Carbon::now()->timezone(config('app.timezone'))->format('Y-m-d H:i:sO');

        $query = [
            'user' => $this->adminUserData['name'],
            'model' => 'User',
            'auditable_id' => $createdUser['id'],
            'type' => 'updated',
            'dateStart' => $date,
            'dateEnd' => $date
        ];

        $response = $this->get($this->apiPath . '/audit?' . http_build_query($query), $header);
        $response->assertStatus(200);
    }
}
