<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local-only accounts with known credentials, one per role, so each role can
 * be exercised in the UI. Deliberately NOT called from DatabaseSeeder — run it
 * explicitly:
 *
 *     php artisan db:seed --class=DemoUsersSeeder
 *
 * Re-running resets these three accounts (handy if you change their password
 * while testing). It only ever touches demo.* addresses, never real users.
 */
class DemoUsersSeeder extends Seeder
{
    public const PASSWORD = 'password123';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Refusing to seed demo accounts in production.');
            return;
        }

        // Same department so the manager can actually see the employee in
        // department-scoped reports.
        $department = Department::first();

        if (!$department) {
            $this->command?->error('No departments exist. Create one first.');
            return;
        }

        $accounts = [
            ['email' => 'demo.admin@local.test',    'name' => 'Demo Admin',    'role' => 'admin',    'pin' => '1111'],
            ['email' => 'demo.manager@local.test',  'name' => 'Demo Manager',  'role' => 'manager',  'pin' => '2222'],
            ['email' => 'demo.employee@local.test', 'name' => 'Demo Employee', 'role' => 'employee', 'pin' => '3333'],
        ];

        foreach ($accounts as $account) {
            $role = Role::whereRaw('LOWER(name) = ?', [$account['role']])->first();

            if (!$role) {
                $this->command?->error("Role {$account['role']} not found, skipping {$account['email']}.");
                continue;
            }

            User::withTrashed()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'pin' => Hash::make($account['pin']),
                    'role_id' => $role->id,
                    'department_id' => $department->id,
                    'is_active' => true,
                    // Off, so these log straight in instead of hitting the
                    // forced credential-change gate.
                    'must_change_password' => false,
                    'must_change_pin' => false,
                    'deleted_at' => null,
                ]
            );

            $this->command?->line("  {$account['email']}  /  " . self::PASSWORD . "  /  PIN {$account['pin']}  ({$account['role']})");
        }

        $this->command?->info("Demo accounts ready in department: {$department->name}");
    }
}
