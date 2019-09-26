<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Product;
use App\User;
use Faker\Generator as Faker;

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->paragraph(1),
        'quantity' => $faker->numberBetween(1, 10),
        'status' => $faker->randomElement([Product::AVAILABE_PRODUCT, Product::UNAVAILABE_PRODUCT]),
        'image' => $faker->randomElement(['book1.jpeg', 'book2.jpeg', 'book3.jpeg']),
        'seller_id' => User::inRandomOrder()->first()->id,
    ];
});
