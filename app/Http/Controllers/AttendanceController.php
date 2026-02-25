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
}
