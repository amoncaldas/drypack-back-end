<?php

namespace Tests\Unit;

use Tests\TestCase;

class DynamicQueryTest extends TestCase
{
    /**
     * Test api search
     */
    public function testApiSearch()
    {
        $header = $this->createAuthHeaderToAdminUser();

        function createCondition($operator, $prop, $value)
        {
            return [
                'op' => $operator,
                'prop' => $prop,
                'value' => $value
            ];
        }

        $filters = [];
        array_push($filters, createCondition('=', 'email', $this->adminUserData['email']));
        array_push($filters, createCondition('like', 'email', $this->adminUserData['email']));
        array_push($filters, createCondition('startswith', 'email', $this->adminUserData['email']));
        array_push($filters, createCondition('endswith', 'email', $this->adminUserData['email']));

        $query = [
            'model' => 'App\User',
            'filters' => json_encode($filters)
        ];

        $response = $this->get($this->apiPath . '/dynamic-query?' . http_build_query($query), $header);
        $response->assertStatus(200);

        $responseData = $response->json();

        $this->assertCount(1, $responseData['items']);
        $this->assertContains($this->adminUserData['email'], collect($responseData['items'])->pluck('email')->all());
    }

    /**
     * Test api load models
     */
    public function testApiLoadModels()
    {
        $response = $this->get($this->apiPath . '/dynamic-query/models', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);

        $responseData = $response->json();

        $response->assertJsonStructure([
            'models' => [
                '*' => [
                    'name', 'props' => [
                        '*' => ['name', 'type']
                    ]
                ]
            ]
        ]);

        // Need to be at least user model
        $this->assertContains('App\User', collect($responseData["models"])->pluck('name')->all());
    }
}
