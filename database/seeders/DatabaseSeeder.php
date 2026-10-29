<?php

namespace Database\Seeders;

use App\Enums\ChurchStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with Phase 1 demo data.
     */
    public function run(): void
    {
        // 1. Central Admin Account
        $centralAdmin = User::updateOrCreate(
            ['email' => 'admin@pabasa.ph'],
            [
                'name' => 'Central Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_central_admin' => true,
                'preferred_locale' => 'en',
            ]
        );

        // 2. Sample Parish / Church
        $church = Church::updateOrCreate(
            ['slug' => 'cebu-cathedral'],
            [
                'name' => 'Cebu Metropolitan Cathedral',
                'status' => ChurchStatus::Published,
                'contact_email' => 'cathedral@cebudiocese.ph',
                'contact_phone' => '+639171234567',
                'address' => 'Mabini St, Parian',
                'city' => 'Cebu City',
                'province' => 'Cebu',
                'postal_code' => '6000',
                'timezone' => 'Asia/Manila',
                'verified_at' => now(),
                'verified_by' => $centralAdmin->id,
                'published_at' => now(),
            ]
        );

        // 3. Church Admin Account
        $churchAdmin = User::updateOrCreate(
            ['email' => 'churchadmin@pabasa.ph'],
            [
                'name' => 'Fr. Roberto Gomez (Parish Admin)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_central_admin' => false,
                'preferred_locale' => 'fil',
            ]
        );

        ChurchMembership::updateOrCreate(
            [
                'church_id' => $church->id,
                'user_id' => $churchAdmin->id,
                'role' => MembershipRole::ChurchAdmin->value,
            ],
            [
                'status' => MembershipStatus::Active,
                'activated_at' => now(),
            ]
        );

        // 4. Liturgical Lector Account
        $lector = User::updateOrCreate(
            ['email' => 'lector@pabasa.ph'],
            [
                'name' => 'Sister Teresa Santos (Lector)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_central_admin' => false,
                'preferred_locale' => 'ceb',
            ]
        );

        ChurchMembership::updateOrCreate(
            [
                'church_id' => $church->id,
                'user_id' => $lector->id,
                'role' => MembershipRole::Lector->value,
            ],
            [
                'status' => MembershipStatus::Active,
                'activated_at' => now(),
            ]
        );

        // 5. Registered Parishioner Account
        User::updateOrCreate(
            ['email' => 'user@pabasa.ph'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_central_admin' => false,
                'preferred_locale' => 'fil',
            ]
        );
    }
}
