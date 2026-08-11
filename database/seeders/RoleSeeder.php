<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * IDs are pinned so the frontend role picker stays in sync.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'manager'],
            ['id' => 3, 'name' => 'employee'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], ['name' => $role['name']]);
        }
    }
}
