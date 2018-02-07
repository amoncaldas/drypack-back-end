<?php

namespace Tests\Unit;

use Tests\TestCase;

class MailTest extends TestCase
{
    /**
     * Test admin api to send mail
     */
    public function testSendMailToUsersWithInvalidMailAddress() {
        $users = factory(\App\User::class, 2)->states('invalid')->make();

        $users = $this->mapEloquentUsers($users);

        //create a mail object
        $mail = [
            'subject' => 'Testing',
            'message' => 'Message Testing',
            'users' => $users
        ];

        $response = $this->post($this->apiPath . '/mails', $mail, $this->createAuthHeaderToAdminUser());

        $response->assertStatus(422);
    }

    /**
     * Test admin api to send mail
     */
    public function testSendMailToUsersWithInvalidMailData() {
        $users = factory(\App\User::class, 1)->make();

        $users = $this->mapEloquentUsers($users);

        //create a mail object
        $mail = [
            'subject' => null,
            'message' => '',
            'users' => $users
        ];

        $response = $this->post($this->apiPath . '/mails', $mail, $this->createAuthHeaderToAdminUser());

        $response->assertStatus(422);
    }

    /**
     * Test admin api to send mail
     */
    public function testSendMailToUsersWithValidData() {
        //generate 4 users
        $users = factory(\App\User::class, 4)->make();

        $users = $this->mapEloquentUsers($users);

        //create a mail object
        $mail = [
            'subject' => 'Testing',
            'message' => 'Message Testing',
            'users' => $users
        ];

        $response = $this->post($this->apiPath . '/mails', $mail, $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);
    }

    /**
     * Map eloquent models users to a simple structure
     *
     * @param  array $users array of Eloquent users
     *
     * @return array simple array with users data to send mail
     */
    private function mapEloquentUsers($users)
    {
        return $users->map(function($item) {
            return [
                'name' => $item->name,
                'email' => $item->email
            ];
        })->toArray();
    }
}
