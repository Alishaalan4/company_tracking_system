<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Http\Resources\AttendanceResource;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
class ReportService
{
    /**
     * Managers only ever see their own department; admins see everything.
     */
    private function scope($user, $query)
    {
        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        return $query;
    }

    public function daily($user, $date)
    {
        $date = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $records = $this->scope($user, Attendance::whereDate('date', $date))
            ->with('user.department')
            ->get();

        return [
            'date' => $date,
            'records' => AttendanceResource::collection($records)->resolve(),
        ];
    }

    public function monthly($user, $month = null, $year = null)
    {
        $today = Carbon::today();
        $month = (int) ($month ?: $today->month);
        $year = (int) ($year ?: $today->year);

        $period = Carbon::create($year, $month, 1);
        $totalDays = $period->daysInMonth;

        $records = $this->scope(
            $user,
            Attendance::whereMonth('date', $month)->whereYear('date', $year)
        )->with('user')->get();

        $leaveDays = $this->leaveDaysByUser($user, $period);

        $rows = $records
            ->groupBy('user_id')
            ->map(function ($rows, $userId) use ($totalDays, $leaveDays) {
                $present = $rows->filter(fn ($r) => $r->check_in_at !== null)->count();
                $absent = $rows->where('is_absent', true)->count();

                return [
                    'user_id' => (int) $userId,
                    'user_name' => optional($rows->first()->user)->name,
                    'total_days' => $totalDays,
                    'present_days' => $present,
                    'absent_days' => $absent,
                    'leave_days' => $leaveDays[$userId] ?? 0,
                    'late_days' => $rows->where('is_late', true)->count(),
                ];
            })
            ->values();

        return [
            'month' => $month,
            'year' => $year,
            'records' => $rows,
        ];
    }

    /**
     * Approved leave days per user that fall inside the given month.
     */
    private function leaveDaysByUser($user, Carbon $period): array
    {
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        $query = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);

        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        $days = [];

        foreach ($query->get() as $leave) {
            $from = $leave->start_date->greaterThan($start) ? $leave->start_date : $start;
            $to = $leave->end_date->lessThan($end) ? $leave->end_date : $end;

            $days[$leave->user_id] = ($days[$leave->user_id] ?? 0) + $from->diffInDays($to) + 1;
        }

        return $days;
    }

    public function summary($user)
    {
        $today = Carbon::today()->toDateString();

        $userQuery = User::query();
        if ($user->isManager()) {
            $userQuery->where('department_id', $user->department_id);
        }
        $totalEmployees = (clone $userQuery)->where('is_active', true)->count();

        $todayQuery = $this->scope($user, Attendance::whereDate('date', $today));

        $leaveQuery = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);

        if ($user->isManager()) {
            $leaveQuery->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        $presentToday = (clone $todayQuery)->whereNotNull('check_in_at')->count();
        $onLeaveToday = $leaveQuery->distinct('user_id')->count('user_id');

        $allTime = $this->scope($user, Attendance::query());

        return [
            // Headline figures the dashboard renders.
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'on_leave_today' => $onLeaveToday,
            'absent_today' => max(0, $totalEmployees - $presentToday - $onLeaveToday),

            // Running totals, also used by the PDF export.
            'total_late' => (clone $allTime)->where('is_late', true)->count(),
            'total_absent' => (clone $allTime)->where('is_absent', true)->count(),
            'total_early' => (clone $allTime)->where('left_early', true)->count(),
        ];
    }

    public function exportPdf($user)
    {
        $data = $this->summary($user);

        $pdf = PDF::loadView('reports.summary', compact('data'));

        return $pdf->download('report.pdf');
    }

    public function exportExcel($user)
    {
        return Excel::download(new AttendanceExport, 'attendance.xlsx');
    }
}
