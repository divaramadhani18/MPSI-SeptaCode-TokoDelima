<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $configuredPassword = env('DEMO_USER_PASSWORD');

        foreach ([
            ['name' => 'Pemilik Toko', 'username' => 'owner', 'role' => 'owner'],
            ['name' => 'Supervisor Toko', 'username' => 'supervisor', 'role' => 'supervisor'],
            ['name' => 'Kasir Toko', 'username' => 'kasir', 'role' => 'kasir'],
        ] as $account) {
            $user = User::where('username', $account['username'])->first();

            if ($user) {
                if ($configuredPassword) {
                    $user->update(['password' => Hash::make($configuredPassword)]);
                }

                continue;
            }

            $password = $configuredPassword ?: Str::password(16);
            User::create($account + ['password' => Hash::make($password), 'is_active' => true]);
            $this->command?->info("Akun awal {$account['username']} dibuat. Kata sandi sementara: {$password}");
        }

        $this->call(SpreadsheetSampleDataSeeder::class);
    }
}
