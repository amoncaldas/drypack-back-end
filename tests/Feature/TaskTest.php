<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Role;
use App\User;
use App\Task;

class TaskTest extends TestCase
{
    /**
     * Test show api
     */
    public function testApiShow()
    {
        $project = factory(\App\Project::class)->create();
        $project->tasks()->saveMany(factory(\App\Task::class, 2)->states('no-project')->make());

        $task = Task::first();

        $response = $this->get($this->apiPath . '/tasks/' . $task->id, $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);
        $response->assertJsonStructure($this->taskJsonStructure());
    }

    /**
     * Test list api
     */
    public function testApiList()
    {
        $response = $this->get($this->apiPath . '/tasks', $this->createAuthHeaderToAdminUser());
        $response->assertStatus(200);
        $response->assertJsonStructure([ '*' => $this->taskJsonStructure()]);
    }

    /**
     * Test search api
     */
    public function testApiSearch()
    {
        $project = factory(\App\Project::class)->create();
        $task = factory(\App\Task::class)->states('no-project')->make();
        $task->project()->associate($project);
        $task->done = true;
        $task->save();

        $header = $this->createAuthHeaderToAdminUser();
        $query = [
            'perPage' => 1,
            'page' => 1,
            'description' => $task->description,
            'project_id'=> $project->id,
            'done' => true
        ];

        $response = $this->get($this->apiPath . '/tasks?' . http_build_query($query), $header);
        $response->assertStatus(200);
        $response->assertJsonStructure($this->taskAPIJsonStructure());
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['total']);
    }

    /**
     * Test api search tasks provoking a sql error with a invalid property
     */
    public function testApiSearchWithInvalidProperty()
    {
        $query = [
            'prop'=>'"',// invalid property
            'op'=>'=',
            'value'=>'does-not-matter'
        ];
        $header = $this->createAuthHeaderToAdminUser();

        $response = $this->get($this->apiPath . '/tasks?'.http_build_query($query), $header);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test create task api
     */
    public function testBasicUserShouldCreateTaskWithValidData()
    {
        $project = factory(\App\Project::class)->create();
        $task = factory(\App\Task::class)->states('no-project')->make();
        $task->project()->associate($project);

        // change eloquent model to a simple array
        $task = $task->toArray();

        $response = $this->post($this->apiPath . '/tasks', $task, $this->createAuthHeaderToBasicUser());

        $response->assertStatus(200);
        $response->assertJsonStructure($this->taskJsonStructure());
        $responseData = $response->json();
        $this->assertEquals($task['description'], $responseData['description']);
    }


    /**
     * Test update task api
     */
    public function testBasicUserShouldUpdateTaskWithValidData()
    {
        $project = factory(\App\Project::class)->create();
        $task = factory(\App\Task::class)->states('no-project')->make();
        $task->project()->associate($project);
        $task->save();

        $task->description = $this->faker->name;

        //change eloquent model to a simple array
        $task = $task->toArray();

        $response = $this->put(
            $this->apiPath . '/tasks/' . $task['id'],
            $task,
            $this->createAuthHeaderToBasicUser()
        );

        $response->assertStatus(200);
        $response->assertJsonStructure($this->taskJsonStructure());
        $responseData = $response->json();
        $this->assertEquals($task['description'], $responseData['description']);
    }


    /**
     * Test delete task api
     */
    public function testBasicUserShouldDeleteTask()
    {
        $project = factory(\App\Project::class)->create();
        $task = factory(\App\Task::class)->states('no-project')->make();
        $task->project_id = $project->id;
        $task->save();

        $response = $this->delete($this->apiPath . '/tasks/'. $task->id, [], $this->createAuthHeaderToBasicUser());

        $response->assertStatus(200);
    }

    /**
     * Return the task api structure expected
     */
    private function taskAPIJsonStructure(){
        return [
            'total',
            'items' => [
                '*' => $this->taskJsonStructure()
            ]
        ];
    }

    /**
     * Return the task structure expected
     */
    private function taskJsonStructure(){
        return [
            'id',
            'description',
            'done',
            'priority',
            'scheduled_to',
            'project_id',
            'created_at',
            'updated_at'
        ];
    }
}
