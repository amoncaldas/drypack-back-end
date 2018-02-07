<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\User;
use App\Authorization\AuthorizationSetup;
use App\Authorization\Action;

class AuthorizationTest extends TestCase
{
    /**
     * This dummy resource controller is intended to be used to test the case
     * when an resource (represented by a controller) exist but is not declared on the
     * config/authorization.php
     */
    public function testApiNotExistingResource()
    {
        $response = $this->get($this->apiPath . '/dummy-resource/method', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(403);
        $response->assertJsonStructure(['error']);
    }

    /**
     * This dummy authorization controller is intended to be used to test the case
     * when an resource a resource is listed in the config/authorization.php file but one of its actions (represented by a method)
     * exist but is not listed
     */
    public function testApiNotExistingAction()
    {
        $response = $this->get($this->apiPath . '/dummy-action/method', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(403);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test api to get resources
     */
    public function testApiGetResources()
    {
        $response = $this->get($this->apiPath . '/authorization/resources', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);
        $responseData = $response->json();
        $struct = ['*'=>$this->resourceJsonStructure()];
        $response->assertJsonStructure($struct);
    }

    /**
     * Test api get authorization actions and the GenericServices
     */
    public function testApiGetActions()
    {
        $response = $this->get($this->apiPath . '/authorization/actions', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);
        $responseData = $response->json();
        $response->assertJsonStructure($this->genericServiceActionsJsonStructure());
    }

    /**
     * Test api get authorization and the GenericServices with non existing method
     */
    public function testApiNotExistingMethod()
    {
        $response = $this->get($this->apiPath . '/authorization/non-existing-method', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(404);
    }

    /**
     * Test api search authorization actions and the GenericServices with single filter containing a no existing property
     */
    public function testApiSearchActionsWithNotExistingProperty()
    {
        $resource_filter = 'roles';
        $query = [
            'skip' => 0,
            'take' => 10,
            'prop'=>'invalid_column',
            'op'=>'==',
            'value'=>$resource_filter
        ];
        $header = $this->createAuthHeaderToAdminUser();

        $path = '/authorization/actions?'.http_build_query($query);
        $response = $this->get($this->apiPath . $path, $header);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

     /**
     * Test api authorization actions search and the GenericServices with multiple filters
     */
    public function testApiSearchActionsWithMultipleFilters()
    {
        $query = [
            'filters'=>'[{"prop":"resource_slug","op":"=","value":"roles"}, {"prop":"resource_slug","op":"!=","value":"users"}]',
        ];

        $header = $this->createAuthHeaderToAdminUser();

        $response = $this->get($this->apiPath . '/authorization/actions?'.http_build_query($query), $header);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->genericServiceActionsJsonStructure());
    }

    /**
     * Test api search authorization actions and the GenericServices
     */
    public function testApiSearchActions()
    {
        $resource_filter = 'roles';
        $query = [
            'skip' => 0,
            'take' => 10,
            'prop'=>'resource_slug',
            'op'=>'=',
            'value'=>$resource_filter
        ];
        $header = $this->createAuthHeaderToAdminUser();

        // Get all effective permissions (we discard the one that are wildcards)
        $actions = Action::where('resource_slug', "=", $resource_filter)
        ->get()->toArray();

        $response = $this->get($this->apiPath . '/authorization/actions?' . http_build_query($query), $header);
        $response->assertStatus(200);
        $responseData = $response->json();

        $response->assertJsonStructure($this->genericServiceActionsJsonStructure());
    }



    /**
     * Return the default action json structure
     */
    private function actionJsonStructure(){
        return [
            'id',
            'action_type_slug',
            'resource_slug',
            'dependencies'=>['*'=>
                [
                    'id',
                    'action_type_slug',
                    'resource_slug',
                    'action_type_name',
                ]
            ],
            'action_type_name',
        ];
    }

    /**
     * Return the default action json structure
     */
    private function resourceJsonStructure(){
        return [
            'name',
            'slug',
            'actions' => [
                '*'=>$this->actionJsonStructure()
            ]
        ];
    }

     /**
     * Return the default action json structure
     */
    private function genericServiceActionsJsonStructure(){
        return [
            'total',
            'items_count',
            'items'=>[
                '*'=>$this->actionJsonStructure()
            ]
        ];
    }

}
