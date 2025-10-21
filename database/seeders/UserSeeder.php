<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'email_verified_at' => now(),
                'password' => \Hash::make('password'),
                'remember_token' => \Str::random(10)
            ],
            [
                'name' => 'Admin Destinasi',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => \Hash::make('password'),
                'remember_token' => \Str::random(10)
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::create($user);
        }

        // Assign roles
        $superAdmin = \App\Models\User::where('email', 'superadmin@example.com')->first();

        DB::table('role_user')->insert([
            'user_id' => $superAdmin->id,
            'role_id' => \App\Models\Role::where('name', 'super_admin')->first()->id,
        ]);
    }
}
