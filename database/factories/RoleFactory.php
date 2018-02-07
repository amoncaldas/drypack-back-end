<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */


$factory->define(App\Role::class, function (Faker\Generator $faker) {
    static $password;
    return [
        'title' => $faker->unique()->name,
        'slug' => $faker->unique()->slug
    ];
});

$factory->state(App\Role::class, 'admin', function () {
    return [
        'title' => \Lang::get("auth.roles.admin"),
        'slug' => 'admin'
    ];
});

$factory->state(App\Role::class, 'basic', function () {
    return [
        'title' => \Lang::get("auth.roles.basic"),
        'slug' => 'basic'
    ];
});

$factory->state(App\Role::class, 'anonymous', function () {
    return [
        'title' => \Lang::get("auth.roles.anonymous"),
        'slug' => 'anonymous'
    ];
});

$factory->state(App\Role::class, 'invalid', function () {
    return [
        'title' => null,
    ];
});
