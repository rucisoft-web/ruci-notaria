<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(JenisLayananSeeder::class);

        if ($email = config('notaria.admin.email')) {
            User::updateOrCreate(['email' => $email], [
                'name' => 'Notaris',
                'password' => config('notaria.admin.password') ?: str()->password(16),
                'role' => Role::Notaris,
                'is_active' => true,
            ]);
            $this->command?->info("Akun notaris: {$email}");
        }

        if (config('notaria.demo.enabled')) {
            $this->call(DemoSeeder::class);
        }
    }
}
