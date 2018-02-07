<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */


$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10)
    ];
});

$factory->state(App\User::class, 'invalid', function () {
    return [
        'email' => '631837y1t3615361',
    ];
});


$factory->state(App\User::class, 'admin', function () {
    return [
        'name' => env('DEFAULT_ADMIN_USER_NAME'),
        'email' => env('DEFAULT_ADMIN_USER_EMAIL'),
        'password' => Hash::make(env('DEFAULT_ADMIN_USER_PASS'))
    ];
});

$factory->state(App\User::class, 'admin-plain-password', function () {
    return [
        'name' => env('DEFAULT_ADMIN_USER_NAME'),
        'email' => env('DEFAULT_ADMIN_USER_EMAIL'),
        'password' => env('DEFAULT_ADMIN_USER_PASS')
    ];
});


$factory->state(App\User::class, 'basic', function () {
    return [
        'email' => env('DEFAULT_BASIC_USER_EMAIL'),
        'password' => Hash::make(env('DEFAULT_BASIC_USER_PASS'))
    ];
});

$factory->state(App\User::class, 'basic-plain-password', function () {
    return [
        'name' => env('DEFAULT_BASIC_USER_NAME'),
        'email' => env('DEFAULT_BASIC_USER_EMAIL'),
        'password' => env('DEFAULT_BASIC_USER_PASS')
    ];
});

$factory->state(App\User::class, 'rest', function () {
    return [
        'roles' => []
    ];
});
