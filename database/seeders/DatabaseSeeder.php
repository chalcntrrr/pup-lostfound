<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Starting data: categories, a Super Admin, an Admin and a few sample posts (the system is for the office only).
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

        if (Item::count() > 0) {
            return;
        }

        $cat = Category::pluck('id', 'name');

        // FOUND items surrendered to the office (logged by the admin).
        $found = [
            ['Electronics', 'Black wireless earbuds', 'Black case with a small scratch on the lid. Found under a bench.', 'Court Bleacher', 2, '15:30'],
            ['IDs & Documents', 'PUP school ID', 'Student ID with a blue lanyard, surrendered to the guard.', 'Main Lobby', 1, '08:10'],
            ['Clothing', 'Maroon PUP jacket', 'Size M, left on a chair after the event.', 'Room 201', 5, '17:45'],
        ];

        foreach ($found as [$category, $name, $description, $location, $daysAgo, $time]) {
            Item::create([
                'user_id' => $admin->id,
                'category_id' => $cat[$category],
                'type' => 'found',
                'name' => $name,
                'description' => $description,
                'location' => $location,
                'date_happened' => now()->subDays($daysAgo)->toDateString(),
                'time_happened' => $time,
            ]);
        }

        // LOST reports logged by the admin for people at the office (walk-in owners, no account).
        $lost = [
            ['Bags & Wallets', 'Brown leather wallet', 'Brown wallet with an ID and some cash. Lost around lunch time.', 'Kubo', 3, '12:00', 'Dela Cruz, Juan', '2023-00123-SR-0', '0912-345-6789'],
            ['Electronics', 'White wireless earbuds', 'White earbuds in a charging case with a sticker.', 'Room 302', 4, null, 'Santos, Maria', 'FAC-2019-0042', '0918-555-0101'],
            ['Others', 'Blue umbrella', 'Large blue umbrella with a wooden handle.', 'Canteen', 1, '13:00', 'Reyes, Carlo', '2022-01234-SR-0', '0920-111-2233'],
        ];

        foreach ($lost as [$category, $name, $description, $location, $daysAgo, $time, $owner, $ownerId, $ownerContact]) {
            Item::create([
                'user_id' => $admin->id, // the admin who logged it
                'category_id' => $cat[$category],
                'type' => 'lost',
                'name' => $name,
                'description' => $description,
                'location' => $location,
                'date_happened' => now()->subDays($daysAgo)->toDateString(),
                'time_happened' => $time,
                'owner_name' => $owner,
                'owner_id_number' => $ownerId,
                'owner_contact' => $ownerContact,
            ]);
        }

        // Who surrendered the earbuds (admins only see this); the other found items stay anonymous.
        Item::where('name', 'Black wireless earbuds')->update([
            'finder_name' => 'Reyes, Ana',
            'finder_student_id' => '2024-00001-SR-0',
            'finder_contact' => '0917-123-4567',
        ]);
        Item::where('type', 'found')->whereNull('finder_name')->update(['is_anonymous' => true]);
    }
}
