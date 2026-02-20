<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\NonWorkingDay;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceService
{
    public function handleCheck($user, $pin)
    {
        if (!Hash::check($pin, $user->pin)) {
            return response()->json(['message' => 'Invalid PIN'], 422);
        }

        $today = Carbon::today();
        $now   = Carbon::now();

        // Skip non-working days
        if (NonWorkingDay::where('date', $today)->exists()) {
            return response()->json(['message' => 'Non-working day']);
        }

        // Skip if on approved leave
        $onLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($onLeave) {
            return response()->json(['message' => 'You are on leave today']);
        }

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date'    => $today
            ]
        );

        $department = $user->department;

        $workStart = Carbon::parse($department->work_start);
        $workEnd   = Carbon::parse($department->work_end);

        if (!$attendance->check_in_at) {

            $attendance->check_in_at = $now;

            $lateLimit = $workStart->copy()->addMinutes($department->late_after);

            if ($now->gt($lateLimit)) {
                $attendance->is_late = true;
            }

            $attendance->save();

            return response()->json(['message' => 'Checked in']);
        }

        if (!$attendance->check_out_at) {

            $attendance->check_out_at = $now;

            $earlyLimit = $workEnd->copy()->subMinutes($department->early_leave_before);

            if ($now->lt($earlyLimit)) {
                $attendance->left_early = true;
            }

            $attendance->save();

            return response()->json(['message' => 'Checked out']);
        }

        return response()->json(['message' => 'Already completed'], 422);
    }

    public function history($user)
    {
        return Attendance::where('user_id', $user->id)
            ->latest('date')
            ->paginate(30);
    }
}