<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Role;
use App\User;
use App\Project;

class ProjectTest extends TestCase
{
    /**
     * Test show api anonymously
     */
    public function testApiShowAnonymously()
    {
        $project = factory(\App\Project::class)->create();
        $project->tasks()->saveMany(factory(\App\Task::class, 2)->states('no-project')->make());

        $response = $this->get($this->apiPath . '/projects/' . $project->id);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->projectJsonStructure());
    }

    /**
     * Test list api anonymously
     */
    public function testApiListAnonymously()
    {
        $response = $this->get($this->apiPath . '/projects');
        $response->assertStatus(200);
        $response->assertJsonStructure([ '*' => $this->projectJsonStructure()]);
    }

    /**
     * Test search api
     */
    public function testApiSearch()
    {
        $project = factory(\App\Project::class)->create();

        $project->tasks()->saveMany(factory(\App\Task::class, 2)->states('no-project')->make());

        $header = $this->createAuthHeaderToAdminUser();
        $query = [
            'perPage' => 1,
            'page' => 1,
            'name' => $project->name
        ];

        $response = $this->get($this->apiPath . '/projects?' . http_build_query($query), $header);
        $response->assertStatus(200);

        $response->assertJsonStructure($this->projectAPIJsonStructure());
    }

    /**
     * Test update project api
     */
    public function testBasicUserShouldUpdateProjectWithValidData()
    {
        $project = factory(\App\Project::class)->create();
        $project->name = $this->faker->name;
        $project->tasks()->saveMany(factory(\App\Task::class, 2)->states('no-project')->make());

        //change eloquent model to a simple array
        $project = $project->toArray();

        $response = $this->put(
            $this->apiPath . '/projects/' . $project['id'],
            $project,
            $this->createAuthHeaderToBasicUser()
        );

        $response->assertStatus(200);
        $responseData = $response->json();
        $response->assertJsonStructure($this->projectJsonStructure());
        $this->assertEquals($project['name'], $responseData['name']);
    }

    /**
     * Test create project api
     */
    public function testBasicUserShouldCreateProjectWithValidData()
    {
        $project = factory(\App\Project::class)->make();

        // change eloquent model to a simple array
        $project = $project->toArray();

        $response = $this->post(
            $this->apiPath . '/projects/',
            $project,
            $this->createAuthHeaderToBasicUser()
        );

        $response->assertStatus(200);
        // check if the created has the expected structure
        $response->assertJsonStructure($this->projectJsonStructure());
    }

    /**
     * Test delete project api
     */
    public function testBasicUserShouldDeleteProject()
    {
        $project = factory(\App\Project::class)->create();

        $response = $this->delete($this->apiPath . '/projects/'. $project->id, [], $this->createAuthHeaderToBasicUser());

        $response->assertStatus(200);
    }

    /**
     * Return the project api structure expected
     */
    private function projectAPIJsonStructure(){
        return [
            'total',
            'items' => [
                '*' => $this->projectJsonStructure()
            ]
        ];
    }

    /**
     * Return the project structure expected
     */
    private function projectJsonStructure(){
        return [
            'id',
            'name',
            'cost',
            'created_at',
            'updated_at'
        ];
    }
}
