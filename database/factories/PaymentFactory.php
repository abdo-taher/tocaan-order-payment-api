<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::Pending,
            'transaction_id' => null,
        ];
    }

    /**
     * Set the payment as successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Successful,
            'transaction_id' => fake()->uuid(),
        ]);
    }

    /**
     * Set the payment as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
