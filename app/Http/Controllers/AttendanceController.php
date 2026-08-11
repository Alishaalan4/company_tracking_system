<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string',
        ]);

        return $this->attendanceService->checkIn(
            $request->user(),
            $validated['pin'],
        );
    }

    public function checkOut(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string',
        ]);

        return $this->attendanceService->checkOut(
            $request->user(),
            $validated['pin'],
        );
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'pin' => 'required|string',
        ]);

        return $this->attendanceService->handleCheck(
            $request->user(),
            $validated['pin'],
        );
    }

    public function history(Request $request)
    {
        return $this->attendanceService->history($request->user());
    }

    public function status(Request $request)
    {
        $date = \Carbon\Carbon::today();
        $attendance = \App\Models\Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'is_checked_in' => false,
            ]);
        }
        
        return response()->json([
            'is_checked_in' => $attendance->check_in_at && !$attendance->check_out_at,
            'check_in_time' => $attendance->check_in_at,
            'check_out_time' => $attendance->check_out_at
        ]);
    }
}
