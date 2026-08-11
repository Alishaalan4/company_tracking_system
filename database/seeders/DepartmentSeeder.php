<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data only — never add to an environment that already has real departments.
        if (Department::exists()) {
            $this->command?->info('Departments already exist, skipping.');
            return;
        }

        $departments = [
            [
                'name' => 'General',
                'work_start' => '09:00:00',
                'work_end' => '17:00:00',
                'late_after' => 15,
                'early_leave_before' => 15,
            ],
            [
                'name' => 'Engineering',
                'work_start' => '10:00:00',
                'work_end' => '18:00:00',
                'late_after' => 30,
                'early_leave_before' => 30,
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['name' => $department['name']], $department);
        }
    }
}
