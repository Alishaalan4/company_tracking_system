<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data only — never add to an environment that already has real leave types.
        if (LeaveType::exists()) {
            $this->command?->info('Leave types already exist, skipping.');
            return;
        }

        $types = [
            ['name' => 'Annual', 'annual_limit' => 21],
            ['name' => 'Sick', 'annual_limit' => 10],
            ['name' => 'Unpaid', 'annual_limit' => null],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
