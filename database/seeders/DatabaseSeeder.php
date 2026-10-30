<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\ChurchStatus;
use App\Enums\IntentionStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MassStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use App\Enums\PaymentStatus;
use App\Enums\PricingStatus;
use App\Enums\UserStatus;
use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\ChurchCategory;
use App\Models\ChurchMembership;
use App\Models\Intention;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\PaymentException;
use App\Models\PricingConfiguration;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data across all system roles.
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

        // 2. Sample Parish 1: Cebu Metropolitan Cathedral (Published & Verified)
        $church = Church::updateOrCreate(
            ['slug' => 'cebu-cathedral'],
            [
                'name' => 'Cebu Metropolitan Cathedral',
                'status' => ChurchStatus::Published,
                'contact_email' => 'cathedral@cebudiocese.ph',
                'contact_phone' => '+63 32 253 6422',
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

        // Parish Branding
        ChurchBranding::updateOrCreate(
            ['church_id' => $church->id],
            [
                'theme_color' => '#5C1D24',
                'public_description' => 'Mother Church of the Archdiocese of Cebu, consecrated to the Holy Guardian Angels.',
                'contact_email' => 'cathedral@cebudiocese.ph',
                'contact_phone' => '+63 32 253 6422',
                'approved_at' => now(),
                'approved_by' => $centralAdmin->id,
            ]
        );

        // Intention Categories
        $catThanksgiving = ChurchCategory::updateOrCreate(
            ['church_id' => $church->id, 'key' => 'thanksgiving'],
            [
                'label' => 'Thanksgiving Mass',
                'type' => CategoryType::Intention->value,
                'template_type' => 'thanksgiving',
                'price' => '150.00',
                'form_config' => [
                    'occasions' => [
                        'Birthday Anniversary',
                        'Wedding Anniversary',
                        'Anniversary',
                        'Passing the Exam',
                        'New Job/Promotion',
                        'Recovery/ Good health',
                        'Graces & Blessing received',
                        'Good Harvest',
                    ],
                ],
                'color' => '#10B981',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $catSouls = ChurchCategory::updateOrCreate(
            ['church_id' => $church->id, 'key' => 'souls'],
            [
                'label' => 'Mass for the Souls of',
                'type' => CategoryType::Intention->value,
                'template_type' => 'souls',
                'price' => '150.00',
                'form_config' => [
                    'max_souls' => 10,
                ],
                'color' => '#6366F1',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $catPetition = ChurchCategory::updateOrCreate(
            ['church_id' => $church->id, 'key' => 'petition'],
            [
                'label' => 'Special Mass Intention',
                'type' => CategoryType::Intention->value,
                'template_type' => 'special',
                'price' => '150.00',
                'form_config' => [
                    'notes' => "1. We do not pay for the mass. Your donation (Arancel) is for the Parish Church operational expenses and evangelization programs.\n2. The inclusion in the intention does not substitute for the donor's presence in the Holy Mass.\n3. Write your intentions legibly.",
                ],
                'color' => '#F59E0B',
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        $catHealing = ChurchCategory::updateOrCreate(
            ['church_id' => $church->id, 'key' => 'healing'],
            [
                'label' => 'Healing & Fast Recovery',
                'type' => CategoryType::Intention->value,
                'template_type' => 'standard',
                'price' => '150.00',
                'color' => '#EC4899',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        // Global Baseline Pricing Schedule (Diocese Archdiocesan Standard)
        PricingConfiguration::updateOrCreate(
            [
                'scope' => 'global',
                'church_id' => null,
                'version' => 1,
            ],
            [
                'name' => 'Archdiocesan Baseline Offering Schedule 2026',
                'status' => PricingStatus::Approved,
                'currency' => 'PHP',
                'pricing_rules' => [
                    'base_price_per_intention' => '100.00',
                    'service_fee' => [
                        'type' => 'none',
                        'amount' => '0.00',
                        'label' => 'Platform Convenience Fee',
                        'is_active' => false,
                    ],
                ],
                'effective_from' => now()->subDays(60),
                'effective_until' => now()->addYears(2),
                'approved_by' => $centralAdmin->id,
                'approved_at' => now()->subDays(60),
                'change_reason' => 'Archdiocesan baseline fee schedule approved for production.',
            ]
        );

        // Approved Parish Pricing Configuration (PHP 150.00 base rate per intention/slip)
        $pricingConfig = PricingConfiguration::updateOrCreate(
            [
                'church_id' => $church->id,
                'version' => 1,
            ],
            [
                'scope' => 'church',
                'name' => 'Parish Standard Offering Schedule 2026',
                'status' => PricingStatus::Approved,
                'currency' => 'PHP',
                'pricing_rules' => [
                    'base_price_per_intention' => '150.00',
                ],
                'effective_from' => now()->subDays(30),
                'effective_until' => now()->addYear(),
                'approved_by' => $centralAdmin->id,
                'approved_at' => now()->subDays(30),
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
        $parishioner = User::updateOrCreate(
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

        // 6. Mass Schedules
        // Mass 1: Upcoming Sunday Solemn Mass (Open for bookings)
        $mass1 = MassSchedule::updateOrCreate(
            ['church_id' => $church->id, 'title' => 'Sunday Solemn Morning Mass'],
            [
                'starts_at' => now()->addDays(2)->setTime(8, 0),
                'ends_at' => now()->addDays(2)->setTime(9, 30),
                'booking_opens_at' => now()->subDays(5),
                'booking_cutoff_at' => now()->addDays(2)->setTime(6, 0),
                'language' => 'English',
                'location_label' => 'Main Nave / High Altar',
                'status' => MassStatus::Scheduled,
                'booking_status' => 'open',
            ]
        );

        // Mass 2: Upcoming Cebuano Mass (Assigned to Lector Sister Teresa)
        $mass2 = MassSchedule::updateOrCreate(
            ['church_id' => $church->id, 'title' => 'Misa sa Domingo (Cebuano)'],
            [
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(11, 15),
                'booking_opens_at' => now()->subDays(5),
                'booking_cutoff_at' => now()->addDays(2)->setTime(8, 0),
                'language' => 'Cebuano',
                'location_label' => 'Main Nave',
                'status' => MassStatus::Scheduled,
                'booking_status' => 'open',
            ]
        );

        MassAssignment::updateOrCreate(
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass2->id,
                'user_id' => $lector->id,
            ],
            [
                'assignment_role' => 'lector',
                'status' => MassAssignmentStatus::Active,
                'assigned_by' => $churchAdmin->id,
                'assigned_at' => now(),
            ]
        );

        // Mass 3: Yesterday's Mass (Past cutoff, closed)
        MassSchedule::updateOrCreate(
            ['church_id' => $church->id, 'title' => 'Weekday Memorial Mass'],
            [
                'starts_at' => now()->subDay()->setTime(18, 0),
                'ends_at' => now()->subDay()->setTime(19, 0),
                'booking_opens_at' => now()->subDays(7),
                'booking_cutoff_at' => now()->subDay()->setTime(16, 0),
                'language' => 'English',
                'location_label' => 'Blessed Sacrament Chapel',
                'status' => MassStatus::Completed,
                'booking_status' => 'closed',
            ]
        );

        // 7. Sample Parish 2: San Pedro Calungsod Parish (Unverified Draft - For Central Admin to review)
        Church::updateOrCreate(
            ['slug' => 'san-pedro-calungsod'],
            [
                'name' => 'San Pedro Calungsod Parish',
                'status' => ChurchStatus::Draft,
                'contact_email' => 'calungsod.parish@example.com',
                'contact_phone' => '+63 32 345 9876',
                'address' => 'Plaridel St, Alang-alang',
                'city' => 'Mandaue City',
                'province' => 'Cebu',
                'postal_code' => '6014',
                'timezone' => 'Asia/Manila',
            ]
        );

        // 8. Sample Orders & GCash Evidence for Central Admin Verification Workbench
        // Create mock PNG on private disk
        $samplePng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $evidenceKey1 = 'payment_evidence/sample_gcash_001.png';
        $evidenceKey2 = 'payment_evidence/sample_gcash_002.png';
        Storage::disk('private')->put($evidenceKey1, $samplePng);
        Storage::disk('private')->put($evidenceKey2, $samplePng);

        // Order 1: Pending Verification (Appears in Central Admin Review Queue)
        $order1 = Order::updateOrCreate(
            ['order_number' => 'ORD-202610-0001'],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'user_id' => $parishioner->id,
                'submitter_name' => 'Juan Dela Cruz',
                'submitter_email' => 'user@pabasa.ph',
                'status' => OrderStatus::PaymentUnderReview,
                'expected_amount' => '100.00',
                'currency' => 'PHP',
                'pricing_configuration_id' => $pricingConfig->id,
                'submitted_at' => now()->subMinutes(20),
                'privacy_acknowledged_at' => now()->subMinutes(20),
            ]
        );

        Intention::updateOrCreate(
            ['order_id' => $order1->id, 'person_or_family_name' => 'Dela Cruz Family'],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'category_id' => $catThanksgiving->id,
                'category_label_snapshot' => $catThanksgiving->label,
                'wording' => 'For good health, safety, and guidance for our whole family.',
                'status' => IntentionStatus::AwaitingPayment,
            ]
        );

        Intention::updateOrCreate(
            ['order_id' => $order1->id, 'person_or_family_name' => '† Teodoro Dela Cruz'],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'category_id' => $catSouls->id,
                'category_label_snapshot' => $catSouls->label,
                'wording' => 'Eternal repose of the soul of our beloved father.',
                'status' => IntentionStatus::AwaitingPayment,
            ]
        );

        $payment1 = Payment::updateOrCreate(
            ['order_id' => $order1->id],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'status' => PaymentStatus::PendingVerification,
                'expected_amount' => '100.00',
                'submitted_amount' => '100.00',
                'submitted_reference' => 'GCASH-98712345601',
                'normalized_reference' => '98712345601',
                'provider' => 'gcash',
            ]
        );

        PaymentEvidence::updateOrCreate(
            ['payment_id' => $payment1->id],
            [
                'church_id' => $church->id,
                'storage_disk' => 'private',
                'storage_key' => $evidenceKey1,
                'original_filename' => 'gcash_screenshot_receipt.png',
                'mime_type' => 'image/png',
                'size_bytes' => strlen($samplePng),
                'uploaded_by_user_id' => $parishioner->id,
            ]
        );

        // Order 2: Needs Review Exception (Underpayment: expected 100.00, submitted 50.00)
        $order2 = Order::updateOrCreate(
            ['order_number' => 'ORD-202610-0002'],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'submitter_name' => 'Maria Clara Santos (Guest)',
                'submitter_email' => 'mariaguest@example.com',
                'status' => OrderStatus::ActionRequired,
                'expected_amount' => '100.00',
                'currency' => 'PHP',
                'pricing_configuration_id' => $pricingConfig->id,
                'submitted_at' => now()->subHours(1),
                'privacy_acknowledged_at' => now()->subHours(1),
            ]
        );

        Intention::updateOrCreate(
            ['order_id' => $order2->id, 'person_or_family_name' => 'Santos Family'],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'category_id' => $catPetition->id,
                'category_label_snapshot' => $catPetition->label,
                'wording' => 'For financial blessings and peaceful household.',
                'status' => IntentionStatus::AwaitingPayment,
            ]
        );

        Intention::updateOrCreate(
            ['order_id' => $order2->id, 'person_or_family_name' => 'Corazon Santos'],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'category_id' => $catHealing->id,
                'category_label_snapshot' => $catHealing->label,
                'wording' => 'Complete recovery from surgery.',
                'status' => IntentionStatus::AwaitingPayment,
            ]
        );

        $payment2 = Payment::updateOrCreate(
            ['order_id' => $order2->id],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'status' => PaymentStatus::NeedsReview,
                'expected_amount' => '100.00',
                'submitted_amount' => '50.00',
                'submitted_reference' => 'GCASH-11223344556',
                'normalized_reference' => '11223344556',
                'provider' => 'gcash',
            ]
        );

        PaymentException::updateOrCreate(
            ['payment_id' => $payment2->id, 'type' => PaymentExceptionType::Underpayment->value],
            [
                'church_id' => $church->id,
                'status' => PaymentExceptionStatus::Open->value,
                'priority' => 'high',
                'description' => 'Submitted GCash amount (PHP 50.00) is less than expected amount (PHP 100.00).',
                'opened_by' => $centralAdmin->id,
            ]
        );

        PaymentEvidence::updateOrCreate(
            ['payment_id' => $payment2->id],
            [
                'church_id' => $church->id,
                'storage_disk' => 'private',
                'storage_key' => $evidenceKey2,
                'original_filename' => 'gcash_short_payment.png',
                'mime_type' => 'image/png',
                'size_bytes' => strlen($samplePng),
            ]
        );

        // Order 3: Confirmed / Verified Order (Already placed on Reading List)
        $order3 = Order::updateOrCreate(
            ['order_number' => 'ORD-202610-0003'],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'user_id' => $parishioner->id,
                'submitter_name' => 'Juan Dela Cruz',
                'submitter_email' => 'user@pabasa.ph',
                'status' => OrderStatus::Confirmed,
                'expected_amount' => '50.00',
                'currency' => 'PHP',
                'pricing_configuration_id' => $pricingConfig->id,
                'confirmed_at' => now()->subHour(),
                'submitted_at' => now()->subHours(2),
                'privacy_acknowledged_at' => now()->subHours(2),
            ]
        );

        $intention3 = Intention::updateOrCreate(
            ['order_id' => $order3->id, 'person_or_family_name' => 'Elena Dela Cruz'],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'category_id' => $catThanksgiving->id,
                'category_label_snapshot' => $catThanksgiving->label,
                'wording' => 'Thanksgiving on 18th Birthday.',
                'status' => IntentionStatus::Confirmed,
            ]
        );

        Payment::updateOrCreate(
            ['order_id' => $order3->id],
            [
                'public_id' => (string) Str::ulid(),
                'church_id' => $church->id,
                'status' => PaymentStatus::Verified,
                'expected_amount' => '50.00',
                'submitted_amount' => '50.00',
                'verified_amount' => '50.00',
                'submitted_reference' => 'GCASH-77889900112',
                'normalized_reference' => '77889900112',
                'provider' => 'gcash',
                'verified_by' => $centralAdmin->id,
                'verified_at' => now()->subHour(),
            ]
        );

        // Populate Reading List for Mass 1
        $readingList = ReadingList::updateOrCreate(
            ['mass_schedule_id' => $mass1->id],
            [
                'church_id' => $church->id,
                'status' => 'preparing',
                'version' => 1,
            ]
        );

        ReadingListEntry::updateOrCreate(
            ['reading_list_id' => $readingList->id, 'intention_id' => $intention3->id],
            [
                'church_id' => $church->id,
                'mass_schedule_id' => $mass1->id,
                'position' => 1,
                'status' => 'pending',
            ]
        );
    }
}
