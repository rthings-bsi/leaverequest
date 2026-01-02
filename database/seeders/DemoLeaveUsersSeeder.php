<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DemoLeaveUsersSeeder extends Seeder
{
    public function run()
    {
        // create roles if not exist
        foreach (['supervisor', 'manager', 'hr', 'admin', 'employee'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // create a department
        $department = 'Engineering';

        // create users
        $employee = User::firstOrCreate([
            'email' => 'employee@example.test'
        ], [
            'name' => 'Demo Employee',
            'password' => bcrypt('password'),
            'department' => $department,
        ]);
        $employee->assignRole('employee');

        $supervisor = User::firstOrCreate([
            'email' => 'supervisor@example.test'
        ], [
            'name' => 'Demo Supervisor',
            'password' => bcrypt('password'),
            'department' => $department,
        ]);
        $supervisor->assignRole('supervisor');

        $manager = User::firstOrCreate([
            'email' => 'manager@example.test'
        ], [
            'name' => 'Demo Manager',
            'password' => bcrypt('password'),
            'department' => $department,
        ]);
        $manager->assignRole('manager');

        $hr = User::firstOrCreate([
            'email' => 'hr@example.test'
        ], [
            'name' => 'Demo HR',
            'password' => bcrypt('password'),
            'department' => 'HR',
        ]);
        $hr->assignRole('hr');

        $admin = User::firstOrCreate([
            'email' => 'admin@example.test'
        ], [
            'name' => 'Demo Admin',
            'password' => bcrypt('password'),
            'department' => 'Admin',
        ]);
        $admin->assignRole('admin');

        $this->command->info('Demo users created: employee@example.test / supervisor@example.test / manager@example.test / hr@example.test with password "password"');
    }
}
