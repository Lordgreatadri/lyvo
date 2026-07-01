<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * LYVO verifies BOTH email and phone with one-time codes (OtpVerificationController)
 * instead of Breeze's signed email-link flow. These tests cover that OTP journey.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a usable OTP for the user with a known plaintext code.
     */
    private function issueCode(User $user, OtpChannel $channel, OtpPurpose $purpose, string $code): void
    {
        VerificationCode::create([
            'user_id' => $user->id,
            'channel' => $channel->value,
            'purpose' => $purpose->value,
            'destination' => $channel === OtpChannel::Email ? $user->email : $user->phone,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function test_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create([
            'phone_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
    }

    public function test_fully_verified_user_is_redirected_from_the_verification_screen(): void
    {
        $user = User::factory()->create([
            'phone' => '0201110000',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('customer.dashboard'));
    }

    public function test_email_can_be_verified_with_a_valid_code(): void
    {
        $user = User::factory()->unverified()->create([
            'phone' => '0201110001',
            'phone_verified_at' => null,
        ]);

        $this->issueCode($user, OtpChannel::Email, OtpPurpose::EmailVerification, '123456');

        $response = $this->actingAs($user)->post(route('otp.verify', 'email'), [
            'code' => '123456',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_an_invalid_code(): void
    {
        $user = User::factory()->unverified()->create([
            'phone' => '0201110002',
            'phone_verified_at' => null,
        ]);

        $this->issueCode($user, OtpChannel::Email, OtpPurpose::EmailVerification, '123456');

        $response = $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('otp.verify', 'email'), [
                'code' => '000000',
            ]);

        $response->assertSessionHasErrorsIn('email', 'code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
