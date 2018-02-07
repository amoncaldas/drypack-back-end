<?php

namespace Tests\Unit;

use Tests\TestCase;

class SupportTest extends TestCase
{
    /**
     * Test api to load laravel attributes langs
     */
    public function testAttributesLangsApi()
    {
        $response = $this->get($this->apiPath . '/support/langs', $this->createAuthHeaderToBasicUser());

        $response->assertStatus(200);
        $response->assertJsonStructure(['attributes']);
    }
}
