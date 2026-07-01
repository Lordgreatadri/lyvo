<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\OperatorVerificationStatus;
use App\Enums\UserStatus;
use App\Models\BusinessCategory;
use App\Models\CustomerProfile;
use App\Models\OperatorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DemoUserSeeder
 * --------------
 * Creates one fully-verified account per type so each dashboard can be reviewed
 * end-to-end: an admin, a customer, an approved operator and a pending operator
 * (to exercise the approval workflow). All use the password "password".
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // ----- Admin -----
        $admin = User::updateOrCreate(
            ['email' => 'admin@lyvo.test'],
            [
                'account_type' => AccountType::Admin,
                'status' => UserStatus::Active,
                'name' => 'LYVO Admin',
                'phone' => '+233200000000',
                'password' => 'password',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
        $admin->syncRoles(AccountType::Admin->defaultRole());

        // ----- Customer -----
        $customer = User::updateOrCreate(
            ['email' => 'customer@lyvo.test'],
            [
                'account_type' => AccountType::Customer,
                'status' => UserStatus::Active,
                'name' => 'Ama Owusu',
                'phone' => '+233200000001',
                'password' => 'password',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
        $customer->syncRoles(AccountType::Customer->defaultRole());
        CustomerProfile::updateOrCreate(['user_id' => $customer->id], []);

        $fashion = BusinessCategory::where('slug', 'fashion')->first();
        $food = BusinessCategory::where('slug', 'food')->first();

        // ----- Approved operator -----
        $operator = User::updateOrCreate(
            ['email' => 'operator@lyvo.test'],
            [
                'account_type' => AccountType::Operator,
                'status' => UserStatus::Active,
                'name' => 'Adwoa Mensah',
                'phone' => '+233200000002',
                'password' => 'password',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
        $operator->syncRoles(AccountType::Operator->defaultRole());
        OperatorProfile::updateOrCreate(
            ['user_id' => $operator->id],
            [
                'business_category_id' => $fashion?->id,
                'business_name' => 'Adwoa Couture',
                'owner_full_name' => 'Adwoa Mensah',
                'business_location' => 'Accra, Greater Accra',
                'business_description' => 'Bespoke African-inspired fashion, tailored to fit.',
                'verification_status' => OperatorVerificationStatus::Approved,
                'submitted_at' => now()->subDays(5),
                'approved_at' => now()->subDays(4),
                'approved_by' => $admin->id,
                'trust_score' => 96,
            ],
        );

        // ----- Pending operator (awaiting admin approval) -----
        $pending = User::updateOrCreate(
            ['email' => 'pending-operator@lyvo.test'],
            [
                'account_type' => AccountType::Operator,
                'status' => UserStatus::Active,
                'name' => 'Yaa Boateng',
                'phone' => '+233200000003',
                'password' => 'password',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );
        $pending->syncRoles(AccountType::Operator->defaultRole());
        OperatorProfile::updateOrCreate(
            ['user_id' => $pending->id],
            [
                'business_category_id' => $food?->id,
                'business_name' => 'Mama Yaa Kitchen',
                'owner_full_name' => 'Yaa Boateng',
                'business_location' => 'Takoradi, Western',
                'business_description' => 'Authentic home-cooked Ghanaian meals, delivered hot.',
                'verification_status' => OperatorVerificationStatus::Pending,
                'submitted_at' => now()->subHours(3),
                'trust_score' => 0,
            ],
        );
    }
}
