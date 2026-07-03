<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\OperatorVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OperatorProfile>
 */
class OperatorProfileFactory extends Factory
{
    protected $model = \App\Models\OperatorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['account_type' => AccountType::Operator->value]),
            'business_name' => fake()->company(),
            'owner_full_name' => fake()->name(),
            'business_location' => fake()->city(),
            'business_description' => fake()->sentence(),
            'verification_status' => OperatorVerificationStatus::Pending->value,
            'trust_score' => 0,
        ];
    }

    /** An approved operator whose dashboard and public listings are unlocked. */
    public function approved(): static
    {
        return $this->state(fn () => [
            'verification_status' => OperatorVerificationStatus::Approved->value,
            'approved_at' => now(),
            'trust_score' => 80,
        ]);
    }
}
