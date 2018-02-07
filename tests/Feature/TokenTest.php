<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use App\User;

class TokenTest extends TestCase
{
    /**
     * Test authentication check with token not provided
     *
     * @return void
     */
    public function testTokenNotProvided()
    {
        $response = $this->get($this->apiPath . '/authenticate/check');
        $response->assertStatus(401);
        $response->assertJson(['error' => 'token_not_provided']);
    }
}
