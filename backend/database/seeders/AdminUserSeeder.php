<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('telepinoy.admin_email');
        $password = config('telepinoy.admin_password');

        if (! $email || ! $password) {
            $this->command?->warn('Admin account was not seeded. Set TELEPINOY_ADMIN_EMAIL and TELEPINOY_ADMIN_PASSWORD in .env.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->forceFill([
            'name' => 'TelePinoy Admin',
            'password' => Hash::make($password),
            'is_admin' => true,
        ])->save();
    }
}
