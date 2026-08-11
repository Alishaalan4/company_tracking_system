<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Bootstrap admin. Every user-creation endpoint requires an authenticated
     * admin, so without this row the system cannot be entered at all.
     *
     * Credentials come from the environment; the defaults are for local dev only.
     */
    public function run(): void
    {
        // Only bootstrap when there is no admin at all; otherwise this would
        // reset a real administrator's password on every db:seed.
        if (User::whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['admin']))->exists()) {
            $this->command?->info('An admin already exists, skipping bootstrap admin.');
            return;
        }

        $email = env('SEED_ADMIN_EMAIL', 'admin@company.test');
        $password = env('SEED_ADMIN_PASSWORD', 'password');
        $pin = (string) env('SEED_ADMIN_PIN', '1234');

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $department = Department::where('name', 'General')->first();

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($password),
                'pin' => Hash::make($pin),
                'role_id' => $adminRole->id,
                'department_id' => $department?->id,
                'is_active' => true,
                'must_change_password' => false,
                'must_change_pin' => false,
            ]
        );

        $this->command?->warn("Seeded admin: {$email} / {$password} (PIN {$pin}) — change these before deploying.");
    }
}
