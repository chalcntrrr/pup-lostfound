<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Starting data: categories, one admin, one sample student and a few sample posts.
     * Run with:  php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        foreach (['Electronics', 'Bags & Wallets', 'IDs & Documents', 'Clothing', 'Others'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        // Super Admin: the only kind of account that can add admins, reset passwords and delete accounts.
        $admin = User::firstOrCreate(
            ['email' => 'admin@pup.edu.ph'],
            [
                'name' => 'Lost & Found Admin',
                'role' => User::ROLE_SUPERADMIN,
                'password' => 'admin123',
            ]
        );

        // A regular admin (office staff): handles items and claims, cannot add accounts.
        User::firstOrCreate(
            ['email' => 'staff@pup.edu.ph'],
            [
                'name' => 'Office Staff',
                'role' => User::ROLE_ADMIN,
                'password' => 'staff123',
            ]
        );

        $student = User::firstOrCreate(
            ['email' => 'student@pup.edu.ph'],
            [
                'name' => 'Juan Dela Cruz',
                'student_number' => '2023-00123-SR-0',
                'role' => User::ROLE_STUDENT,
                'contact_number' => '0912-345-6789',
                'course_section' => 'BSIT 2-1',
                'password' => 'student123',
            ]
        );

        // A faculty member (professor): has an Employee ID instead of a Student ID.
        $faculty = User::firstOrCreate(
            ['email' => 'prof@pup.edu.ph'],
            [
                'name' => 'Prof. Maria Santos',
                'employee_number' => 'FAC-2019-0042',
                'role' => User::ROLE_FACULTY,
                'contact_number' => '0918-555-0101',
                'course_section' => 'College of Computer Studies',
                'password' => 'prof123',
            ]
        );

        if (Item::count() > 0) {
            return;
        }

        $cat = Category::pluck('id', 'name');

        $samples = [
            [$admin, 'found', 'Electronics', 'Black wireless earbuds', 'Black case with a small scratch on the lid. Found under a bench.', 'Court Bleacher', 2, '15:30'],
            [$admin, 'found', 'IDs & Documents', 'PUP school ID', 'Student ID with a blue lanyard, surrendered to the guard.', 'Main Lobby', 1, '08:10'],
            [$student, 'lost', 'Bags & Wallets', 'Brown leather wallet', 'Brown wallet with my ID and some cash. Lost around lunch time.', 'Kubo', 3, '12:00'],
            [$student, 'lost', 'Electronics', 'White wireless earbuds', 'White earbuds in a charging case with a sticker.', 'Room 302', 4, null],
            [$admin, 'found', 'Clothing', 'Maroon PUP jacket', 'Size M, left on a chair after the event.', 'Room 201', 5, '17:45'],
        ];

        foreach ($samples as [$owner, $type, $category, $name, $description, $location, $daysAgo, $time]) {
            Item::create([
                'user_id' => $owner->id,
                'category_id' => $cat[$category],
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'location' => $location,
                'date_happened' => now()->subDays($daysAgo)->toDateString(),
                'time_happened' => $time,
            ]);
        }

        // A professor's lost item, and a LOST item an admin logged for a walk-in owner (no account).
        Item::create([
            'user_id' => $faculty->id,
            'category_id' => $cat['Electronics'],
            'type' => 'lost',
            'name' => 'Silver USB flash drive',
            'description' => 'Silver 64GB flash drive with a red keychain. Contains lecture files.',
            'location' => 'Room 303',
            'date_happened' => now()->subDays(2)->toDateString(),
            'time_happened' => '10:20',
        ]);

        Item::create([
            'user_id' => $admin->id, // the admin who logged it
            'category_id' => $cat['Others'],
            'type' => 'lost',
            'name' => 'Blue umbrella',
            'description' => 'Large blue umbrella with a wooden handle.',
            'location' => 'Canteen',
            'date_happened' => now()->subDays(1)->toDateString(),
            'time_happened' => '13:00',
            'owner_name' => 'Reyes, Carlo',
            'owner_id_number' => '2022-01234-SR-0',
            'owner_contact' => '0920-111-2233',
        ]);

        // Who surrendered the earbuds (admins only see this); the other found items stay anonymous.
        Item::where('name', 'Black wireless earbuds')->update([
            'finder_name' => 'Reyes, Ana',
            'finder_student_id' => '2024-00001-SR-0',
            'finder_contact' => '0917-123-4567',
        ]);
        Item::where('type', 'found')->whereNull('finder_name')->update(['is_anonymous' => true]);
    }
}
