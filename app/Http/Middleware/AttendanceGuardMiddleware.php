<?php

namespace App\Http\Middleware;

use App\Models\Attendance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class AttendanceGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where("user_id", $user->id)
        ->where("date",$today)
        ->first();

        if ($attendance) 
        {
            if($attendance->check_in_at && $attendance->check_out_at)
            {
                return response()->json(["msg"=>"Attendance Already Completed For today"]);
            }
        }
        return $next($request);
    }
}
