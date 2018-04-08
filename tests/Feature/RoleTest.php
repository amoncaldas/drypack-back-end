<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Authorization\AuthorizationSetup;
use App\Authorization\Action;
use App\Role;
use Illuminate\Support\Facades\Config;

class RoleTest extends TestCase
{
    /**
     * Test api to load roles
     */
    public function testApiLoad()
    {
        $response = $this->get($this->apiPath . '/roles', $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => $this->roleJsonStructure()
        ]);

        //need to be at least admin
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    /**
     * Test update role api
     */
    public function testAdminShouldUpdateRoleWithValidData()
    {
        $role = factory(Role::class)->create();

        //change only the title
        $role->title = $this->faker->name;

        // add permissions to the role
        $actions_allowed = ["authentication", "password", "project", "user:updateProfile"];

        // The line below will store the actions to the role in DB
        AuthorizationSetup::setResourcesActionsForRole($actions_allowed, $role);

        //change eloquent model to a simple array
        $role = $role->toArray();

        $response = $this->put(
            $this->apiPath . '/roles/' . $role['id'],
            $role,
            $this->createAuthHeaderToAdminUser()
        );

        $responseData = $response->json();

        $response->assertStatus(200);
        $this->assertEquals($role['title'], $responseData['title']);
    }

    /**
     * Test role creation api with valid data
     */
    public function testAdminShouldCreateRoleWithValidData()
    {
        // Radon role title
        $role['title'] = $this->faker->name;

        // Get all effective permissions (we disgard the one that are wildcards)
        $actions = Action::where('action_type_slug', "!=", "all")
            ->where('resource_slug', "!=", 'all')
            ->get();

        // Add all permissions to the role as a simple array
        $role['actions'] = $actions->toArray();

        $response = $this->post(
            $this->apiPath . '/roles/',
            $role,
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(200);
        $responseData = $response->json();

        // compare actions count
        $this->assertCount(count($role['actions']), $responseData['actions']);

        // check if the created has the expected structure
        $response->assertJsonStructure(
            $this->roleJsonStructure()
        );
    }

    /**
     * Test role creation with invalid data api
     */
    public function testAdminShouldntCreateRoleWithInvalidData()
    {
        $role = factory(Role::class)->states('invalid')->make();
        $role = $role->toArray();

        $response = $this->post(
            $this->apiPath . '/roles/',
            $role,
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(422); // unprocessable entity
    }

    /**
     * Test role update with invalid data api
     */
    public function testAdminShouldntUpdateRoleWithInvalidData()
    {
        $role = factory(Role::class)->create();

        //change only the title to an invalid state (title is mandatory)
        $role->title = null;

        //change eloquent model to simple array
        $role = $role->toArray();

        $response = $this->put(
            $this->apiPath . '/roles/' . $role['id'],
            $role,
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(422); // unprocessable entity
    }

    /**
     * Test delete role api
     */
    public function testAdminShouldDeleteRole()
    {
        $role = factory(Role::class)->create()->toArray();

        $response = $this->delete($this->apiPath . '/roles/'. $role['id'], [], $this->createAuthHeaderToAdminUser());

        $response->assertStatus(200);
    }

    /**
     * Test create role api with a user without the permission
     */
    public function testBasicUserShouldntCreateRoleWithValidData()
    {
        //change only the title
        $role['title'] = $this->faker->name;
        $role['actions'] = Action::all()->pluck('id')->all();

        $response = $this->post(
            $this->apiPath . '/roles/',
            $role,
            $this->createAuthHeaderToBasicUser()
        );

        $responseData = $response->json();

        $response->assertStatus(403); // permission denied (Forbidden)
    }

    /**
     * Test update role api with a user without the permission
     */
    public function testBasicUserShouldndUpdateRole()
    {
        $role = factory(Role::class)->create()->toArray();

        // change eloquent model to simple array
        $role['title'] = null;

        $response = $this->put(
            $this->apiPath . '/roles/' . $role['id'],
            $role,
            $this->createAuthHeaderToBasicUser()
        );

        $response->assertStatus(403); // permission denied (Forbidden)
    }

    /**
     * Test update role api with a user without the permission
     */
    public function testBasicUserShouldntDeleteRole()
    {
        $role = factory(Role::class)->create();

        $response = $this->delete($this->apiPath . '/roles/'. $role->id, [], $this->createAuthHeaderToBasicUser());

        $response->assertStatus(403); // permission denied (Forbidden)
    }

    /**
     * Test minimum permissions should be auto added when update admin role
     */
    public function testMinimumPermissionsShouldBeAutoAddedWhenUpdateAdminRole()
    {
        $adminRole = Role::where('slug', Role::defaultAdminRoleSlug())->first();

        $password_actions_ids = Action::where("resource_slug", "password")->get()->pluck("id", "action_type_slug")->all();

        //change eloquent model to a simple array
        $role = $adminRole->toArray();

        foreach ($role["actions"] as $key => $value) {
            if( in_array($value["id"], $password_actions_ids)) {
                unset($role["actions"][$key]);
            }
        }

        $response = $this->put(
            $this->apiPath . '/roles/' . $role['id'],
            $role,
            $this->createAuthHeaderToAdminUser()
        );

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount($adminRole->actions->count(), $responseData['actions']);
        $this->assertEquals('mandatory_permissions_added', $response->headers->get('Warning'));
    }

     /**
     * Test admin shouldn't delete admin role
     */
    public function testAdminShouldntDeleteAdminRole()
    {
        $adminRole = Role::where('slug', Role::defaultAdminRoleSlug())->first();

        $response = $this->delete($this->apiPath . '/roles/'. $adminRole->id, [], $this->createAuthHeaderToAdminUser());

        $response->assertStatus(400);
    }


    /**
     * Return the default role json structure
     */
    private function roleJsonStructure(){
        return [
            'id',
            'title',
            'slug',
            'actions' => [
                '*' => [
                    'id',
                    'action_type_slug',
                    'resource_slug',
                    'action_type_name',
                    'dependencies',
                ]
            ],
            'can_be_removed'
        ];
    }

}
