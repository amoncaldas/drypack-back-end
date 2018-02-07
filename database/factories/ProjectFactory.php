<?php

$factory->define(App\Project::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->unique()->text($maxNbChars = 20),
        'cost' => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = null)
    ];
});
