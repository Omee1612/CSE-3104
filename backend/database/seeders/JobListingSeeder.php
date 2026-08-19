<?php

namespace Database\Seeders;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobListingSeeder extends Seeder
{
    public function run(): void
    {
        $agency = User::firstOrCreate(
            ['email' => 'agency@alamin.com'],
            [
                'name' => 'Al-Amin Overseas Ltd.',
                'password' => Hash::make('password123'),
                'role' => 'agency',
                'phone' => '+8801700000000',
                'agency' => 'Al-Amin Overseas Ltd.',
            ]
        );

        // Also create a demo worker
        User::firstOrCreate(
            ['email' => 'worker@demo.com'],
            [
                'name' => 'Rahim Uddin',
                'password' => Hash::make('password123'),
                'role' => 'worker',
                'phone' => '+8801800000000',
                'tracking_id' => 'TRK-2026-001',
                'destination' => 'Saudi Arabia',
                'agency' => 'Al-Amin Overseas Ltd.',
            ]
        );

        $jobs = [
            [
                'creator_id' => $agency->id,
                'title' => 'Construction Technician',
                'country' => 'Saudi Arabia',
                'city' => 'Riyadh',
                'salary' => '1,800 SAR / month',
                'agency' => 'Al-Amin Overseas Ltd.',
                'verified' => true
            ],
            [
                'creator_id' => $agency->id,
                'title' => 'Factory Machine Operator',
                'country' => 'Malaysia',
                'city' => 'Johor Bahru',
                'salary' => '1,700 MYR / month',
                'agency' => 'Prime Manpower BD',
                'verified' => true
            ],
            [
                'creator_id' => $agency->id,
                'title' => 'Hotel Housekeeping Staff',
                'country' => 'Qatar',
                'city' => 'Doha',
                'salary' => '1,200 QAR / month',
                'agency' => 'Bismillah Recruiting',
                'verified' => false
            ],
            [
                'creator_id' => $agency->id,
                'title' => 'Warehouse Assistant',
                'country' => 'UAE',
                'city' => 'Dubai',
                'salary' => '1,500 AED / month',
                'agency' => 'Al-Amin Overseas Ltd.',
                'verified' => true
            ],
        ];

        foreach ($jobs as $job) {
            JobListing::create($job);
        }
    }
}
