<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1,1),
            'title' => $this->faker->word(),
            'price' => $this->faker->numberBetween(500, 3000),
            'frequency' => $this->faker->numberBetween(1,3),
            'first_payment_day' => '2025-03-01',
            'first_payment_day' => '2025-04-01',
            'number_of_payments' => 1,
            'url' => $this->faker->url(),
            'memo' => $this->faker->realText(30),
        ];
    }
}
