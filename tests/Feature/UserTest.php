<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Role;
use App\User;

class UserTest extends TestCase
{

    /**
     * Test if a user that is not admin can access user list api
     */
    public function testBasicUserShouldntList()
    {
        $response = $this->get($this->apiPath . '/users', $this->createAuthHeaderToBasicUser());

        $response->assertStatus(403); // permission denied (Forbidden)
    }

    /**
     * Test user show api
     */
    public function testAdminShouldShow()
    {
        $admin = User::where('email', $this->adminUserData['email'])->first();

        $response = $this->get($this->apiPath . '/users/' . $admin->id, $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id', 'email', 'name', 'image', 'created_at', 'updated_at', 'roles' => [
                '*' => [
                    'id', 'title', 'slug'
                ]
            ]
        ]);
    }

    /**
     * Test user list api
     */
    public function testAdminShouldList()
    {
        $response = $this->get($this->apiPath . '/users', $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);

        $response->assertJsonStructure(['*' => [
            'email', 'name', 'roles'
        ]]);
    }

    /**
     * Test user limited list api
     */
    public function testAdminShouldListWithLimit()
    {
        $query = [
            'limit' => 2
        ];

        $response = $this->get(
            $this->apiPath . '/users?' . http_build_query($query),
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());

        $response->assertJsonStructure(['*' => [
            'email', 'name', 'roles'
        ]]);
    }

    /**
     * Test user paginate list api
     */
    public function testAdminShouldListWithPaginate()
    {
        $query = [
            'perPage' => 1,
            'page' => 1
        ];

        $response = $this->get($this->apiPath . '/users?' . http_build_query($query), $this->createAuthHeaderToAdminUser());

        $response->assertJsonStructure([
            'total', 'items' => [
                '*' => ['email', 'name', 'roles']
            ]
        ]);
    }

    /**
     * Test user paginate search api
     */
    public function testAdminShouldSearch()
    {
        $header = $this->createAuthHeaderToAdminUser();
        $query = [
            'perPage' => 1,
            'page' => 1,
            'email' => $this->adminUserData['email']
        ];

        $response = $this->get($this->apiPath . '/users?' . http_build_query($query), $header);
        $response->assertStatus(200);

        $responseData = $response->json();

        $admin = $responseData['items'][0];

        $this->assertCount(1, $responseData['items']);
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($this->adminUserData['email'], $admin['email']);

        //need exclude the specify user
        $query['notUsers'] = $admin['id'];

        $response = $this->get($this->apiPath . '/users?' . http_build_query($query), $header);
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertCount(0, $responseData['items']);

        //need work with multiple ids
        $query['notUsers'] = '10,400';

        $response = $this->get($this->apiPath . '/users?' . http_build_query($query), $header);
        $response->assertStatus(200);

        //check name and nameOrEmail filter
        $query = [
            'name' => $admin['name'],
            'nameOrEmail' => $admin['email']
        ];

        $response = $this->get($this->apiPath . '/users?' . http_build_query($query), $header);
        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertContains($admin['email'], collect($responseData)->pluck('email')->all());
    }

    /**
     * Test if store user api is validating user object
     */
    public function testAdminShouldntCreateUserWithInvalidData()
    {
        $user = factory(\App\User::class)->states('rest', 'invalid')->make();

        $response = $this->post($this->apiPath . '/users', $user->getAttributes(), $this->createAuthHeaderToAdminUser());

        $response->assertStatus(422);
    }

    /**
     * Test store user api
     */
    public function testAdminShouldCreateUserWithValidData()
    {
        $user = factory(\App\User::class)->states('rest')->make();

        $response = $this->post($this->apiPath . '/users', $user->getAttributes(), $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);
    }

    /**
     * Test update user api
     */
    public function testAdminShouldUpdateUserWithValidData()
    {
        $user = factory(\App\User::class)->create();

        //change only the name and mail
        $user->name = $this->faker->name;
        $user->email = $this->faker->unique()->safeEmail;

        //change eloquent model to simple array without password
        $user = collect($user)->except('password')->toArray();
        $user['roles'] = [];

        //add a role to test audit
        $role = Role::where('slug', 'admin')->select('id')->first();

        array_push($user['roles'], $role->getAttributes());

        $response = $this->put(
            $this->apiPath . '/users/' . $user['id'],
            $user,
            $this->createAuthHeaderToAdminUser()
        );

        $responseData = $response->json();

        $response->assertStatus(200);
        $this->assertEquals($user['name'], $responseData['name']);
        $this->assertEquals($user['email'], $responseData['email']);
    }

    /**
     * Test update profile user api
     */
    public function testUserShouldUpdateProfileWithValidData()
    {
        $user = factory(\App\User::class)->create();

        // To be able to update its own profile the user has to have a role
        // which has the "users:updateProfile" permission. We assing this role here
        $basic_role_slug = Role::defaultBasicRoleSlug();
        $normalRole = Role::where('slug', $basic_role_slug)->first();
        $user->roles()->attach($normalRole->id);

        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $this->faker->password,
            'roles'=>[]
        ];

        $data['password_confirmation'] = $data['password'];

        $header = $this->createAuthHeaderToUser($user);

        $response = $this->put($this->apiPath . '/profile', $data, $header);

        $responseData = $response->json();

        $response->assertStatus(200);
        $this->assertEquals($data['name'], $responseData['name']);
        $this->assertEquals($data['email'], $responseData['email']);
    }

    /**
     * Test delete user api
     */
    public function testAdminShouldDeleteUser()
    {
        $user = factory(\App\User::class)->create();

        $response = $this->delete($this->apiPath . '/users/' . $user->id, [], $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);
    }

    /**
     * Test delete denied for first admin
     */
    public function testAdminShouldntDeleteFirstAdmin()
    {
        // Create a user with admin profile
        $user = factory(\App\User::class)->create();

        // Assign the admin role to the user so it can have the permission to delete users
        $adminRole = Role::where('slug', Role::defaultAdminRoleSlug())->first();
        $user->roles()->attach($adminRole->id);
        $user->save();

        // create auth header for this second admin user
        $header = $this->createAuthHeaderToUser($user);

        // get the first admin user
        $firstAdmin = User::whereHas('roles', function ($query) {
            $query->where('slug', Role::defaultAdminRoleSlug());
        })->orderBy("id", "asc")->first();

        // tries to delete the first admin authenticated as a second admi
        $response = $this->delete($this->apiPath . '/users/' . $firstAdmin->id, [], $header);

        $response->assertStatus(400);
    }

    /**
     * Test user denied to delete itself
     */
    public function testUserShouldntBeAbleToDeleteItself()
    {
        $user = factory(\App\User::class)->create();
        // Assign the admin role to the user so it can have the permission to delete users
        $adminRole = Role::where('slug', Role::defaultAdminRoleSlug())->first();
        $user->roles()->attach($adminRole->id);
        $user->save();

        // create auth header for this second admin user
        $header = $this->createAuthHeaderToUser($user);

        $response = $this->delete($this->apiPath . '/users/' . $user->id, [], $header);

        $response->assertStatus(400);
    }

    /**
     * Test admin role/profile should be auto assigned when update first admin if not assigned
     */
    public function testAdminRoleShouldBeAutoAddedWhenUpdateAdminUser()
    {
         // get the first admin user
        $firstAdmin = User::whereHas('roles', function ($query) {
            $query->where('slug', Role::defaultAdminRoleSlug());
        })->orderBy("id", "asc")->first();

        $user = $firstAdmin->toArray();

        $user['roles'] = [];

        $response = $this->put(
            $this->apiPath . '/users/' . $user['id'],
            $user,
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount($firstAdmin->roles->count(), $responseData['roles']);
        $this->assertEquals('mandatory_admin_profile_auto_added', $response->headers->get('Warning'));
    }
}
