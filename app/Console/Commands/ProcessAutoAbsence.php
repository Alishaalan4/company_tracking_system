<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\NonWorkingDay;
use Carbon\Carbon;
class ProcessAutoAbsence extends Command
{
protected $signature = 'attendance:auto-absence';
    protected $description = 'Mark absent users automatically';

    public function handle()
    {
        $today = Carbon::today();

        // Skip non-working day
        if (NonWorkingDay::whereDate('date', $today)->exists()) {
            return;
        }

        $users = User::where('is_active', true)->get();

        foreach ($users as $user) {

            if (!$user->department) continue;

            $onLeave = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($onLeave) continue;

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance) {
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'is_absent' => true
                ]);
            }
        }
    }
}
