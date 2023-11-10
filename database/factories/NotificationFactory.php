<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

$factory->define(\Oza75\LaravelSesComplaints\Models\Notification::class, function(Faker $faker) {
    return [
        "topic_arn" => $this->faker->sentence,
        "source_email" => $this->faker->email,
        "destination_email" => $this->faker->email,
        "subject" => $this->faker->words(5),
        "message_id" => Hash::make("hello"),
        "ses_message_id" => Hash::make('hello world'),
        "type" => $this->faker->randomElement(["bounce", 'complaint']),
        "sent_at" => $this->faker->dateTime(),
    ];
});