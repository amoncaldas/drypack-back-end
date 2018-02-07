<?php

namespace Tests\Unit;

use Tests\TestCase;

class LogTest extends TestCase
{

    /**
     * Test visit logs viewer with invalid credentials
     */
    public function testVisitLogViewerWithInvalidCredentials()
    {
        $response = $this->get($this->webPath . '/developer/log-viewer', [
            'Authorization' =>  'Basic ' . base64_encode('3g178y3h781h:798h381u3891j')
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test visit logs viewer with valid credentials
     */
    public function testVisitLogViewerWithValidCredentials()
    {
        $response = $this->get($this->webPath . '/developer/log-viewer', [
            'Authorization' =>  'Basic ' . base64_encode( getenv('DEVELOPER_ID') . ':' . getenv('DEVELOPER_PASSWORD') )
        ]);

        $response->assertStatus(200);
    }

}
