<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Accounting', 'code' => 'ACC'],
            ['name' => 'Production', 'code' => 'PRD'],
            ['name' => 'General Affairs', 'code' => 'GA'],
            ['name' => 'Procurement', 'code' => 'PCR'],
            ['name' => 'Health, Safety, and Environment', 'code' => 'HSE'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Sustainability', 'code' => 'STB'],
            ['name' => 'Warehouse', 'code' => 'WH'],
        ];

        // Insert ignoring duplicates (safe to run multiple times)
        foreach ($departments as $d) {
            DB::table('departments')->updateOrInsert(
                ['name' => $d['name']],
                ['code' => $d['code'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
