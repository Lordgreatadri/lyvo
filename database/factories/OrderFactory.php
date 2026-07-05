<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\OrderStatus;
use App\Models\OperatorProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 3000);

        return [
            'order_number' => 'LYV-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
            'customer_id' => User::factory()->state(['account_type' => AccountType::Customer->value]),
            'operator_profile_id' => OperatorProfile::factory()->approved(),
            'status' => OrderStatus::PendingPayment->value,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'currency' => 'GHS',
            'delivery_recipient' => fake()->name(),
            'delivery_phone' => '+233201234567',
            'delivery_address' => fake()->streetAddress().', Accra',
            'placed_at' => now(),
        ];
    }

    public function heldBy(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status->value,
            'funds_held_at' => now(),
        ]);
    }
}
