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
    protected $signature = 'attendance:auto-absence {--date= : Date to process (Y-m-d), defaults to today}';

    protected $description = 'Mark absent users automatically';

    public function handle()
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today();

        if ($date->isFuture()) {
            $this->error('Cannot process a future date.');
            return self::FAILURE;
        }

        // Honours recurring entries (e.g. an annual holiday), which a plain
        // whereDate on the exact date would miss.
        if (NonWorkingDay::fallsOn($date)) {
            $this->info("{$date->toDateString()} is a non-working day, nothing to do.");
            return self::SUCCESS;
        }

        $users = User::where('is_active', true)->with('department')->get();
        $marked = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (!$user->department) {
                $skipped++;
                continue;
            }

            $onLeave = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($onLeave) {
                $skipped++;
                continue;
            }

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->first();

            // A row with no check-in still counts as absent. Only skip people
            // who actually turned up.
            if ($attendance && $attendance->check_in_at) {
                $skipped++;
                continue;
            }

            if ($attendance) {
                if (!$attendance->is_absent) {
                    $attendance->update(['is_absent' => true]);
                    $marked++;
                }
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'is_absent' => true,
            ]);
            $marked++;
        }

        $this->info("{$date->toDateString()}: marked {$marked} absent, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
