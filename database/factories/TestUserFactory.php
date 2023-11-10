<?php

use Faker\Generator as Faker;

$factory->define(\Oza75\LaravelSesComplaints\Tests\TestSupport\Models\TestUser::class, function(Faker $faker) {
    return [
        "email" => $faker->email,
        "name" => $faker->name,
    ];
});