<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD', 'password');
        if (empty($password)) {
            throw new \Exception('Please set the ADMIN_PASSWORD environment variable.');
        }

        User::updateOrCreate(
            ['email' => 'admin@proplayas.org'],
            [
                'name' => 'ProPlayas Dev Team',
                'username' => 'ProPlayasDev',
                'password' => Hash::make($password),
                'role' => 'admin',
                'status' => 'activo',
            ]
        );
    }
}
