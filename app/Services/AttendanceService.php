<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\NonWorkingDay;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceService
{
    private function validateCheckContext($user, $pin)
    {
        if (!Hash::check($pin, $user->pin)) {
            return response()->json(['message' => 'Invalid PIN'], 422);
        }

        $today = Carbon::today();

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

        return null;
    }

    private function getTodayAttendance($user)
    {
        return Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => Carbon::today(),
            ]
        );
    }

    public function checkIn($user, $pin)
    {
        $contextError = $this->validateCheckContext($user, $pin);
        if ($contextError) {
            return $contextError;
        }

        $attendance = $this->getTodayAttendance($user);

        if ($attendance->check_in_at) {
            return response()->json(['message' => 'Already checked in today'], 422);
        }

        $now = Carbon::now();
        $department = $user->department;
        $workStart = Carbon::parse($department->work_start);

        $attendance->check_in_at = $now;
        $lateLimit = $workStart->copy()->addMinutes($department->late_after);

        if ($now->gt($lateLimit)) {
            $attendance->is_late = true;
        }

        $attendance->save();

        return response()->json(['message' => 'Checked in']);
    }

    public function checkOut($user, $pin)
    {
        $contextError = $this->validateCheckContext($user, $pin);
        if ($contextError) {
            return $contextError;
        }

        $attendance = $this->getTodayAttendance($user);

        if (!$attendance->check_in_at) {
            return response()->json(['message' => 'No check-in found for today'], 422);
        }

        if ($attendance->check_out_at) {
            return response()->json(['message' => 'Already checked out today'], 422);
        }

        $now = Carbon::now();
        $department = $user->department;
        $workEnd = Carbon::parse($department->work_end);
        $attendance->check_out_at = $now;
        $earlyLimit = $workEnd->copy()->subMinutes($department->early_leave_before);

        if ($now->lt($earlyLimit)) {
            $attendance->left_early = true;
        }

        $attendance->save();

        return response()->json(['message' => 'Checked out']);
    }

    public function handleCheck($user, $pin)
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if (!$attendance || !$attendance->check_in_at) {
            return $this->checkIn($user, $pin);
        }

        return $this->checkOut($user, $pin);
    }

    public function history($user)
    {
        return Attendance::where('user_id', $user->id)
            ->latest('date')
            ->paginate(30);
    }
}
