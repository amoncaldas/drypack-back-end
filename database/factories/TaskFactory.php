<?php

$factory->define(App\Task::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'description' => $faker->unique()->text($maxNbChars = 50),
        'done' => $faker->boolean($chanceOfGettingTrue = 50),
        'priority' => $faker->unique($reset = true)->randomDigitNotNull,
        'scheduled_to' => $faker->optional($weight = 0.9)->dateTimeInInterval($startDate = '-5 days', $interval = '+5 days', $timezone = date_default_timezone_get()),
        'project_id' => function () {
            return factory(App\Project::class)->create()->id;
        }
    ];
});

$factory->state(App\Task::class, 'no-project', function () {
    return [
        'project_id' => null
    ];
});
