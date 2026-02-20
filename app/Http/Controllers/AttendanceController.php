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
    public function check(Request $request)
    {
        $validate = $request->validate([
            "pin"=> "required|string",
        ]);
        return $this->attendanceService->handleCheck(
            $request->user(),
            $validate["pin"],
        );
    }

    public function history(Request $request)
    {
        return $this->attendanceService->history($request->user());   
    }
}
