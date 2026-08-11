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

    /**
     * Builds the rows for an export from the same filters the report tabs use,
     * so a PDF/Excel download matches whatever is on screen. Previously both
     * exports ignored their parameters and always dumped the all-time summary.
     */
    public function exportPayload($user, array $filters = []): array
    {
        $date = $filters['date'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        $time = fn ($iso) => $iso ? Carbon::parse($iso)->format('H:i') : '—';

        if ($date) {
            $report = $this->daily($user, $date);

            return [
                'title' => 'Daily Attendance — ' . $report['date'],
                'filename' => 'attendance-daily-' . $report['date'],
                'headings' => ['Employee', 'Department', 'Check In', 'Check Out', 'Duration', 'Status'],
                'rows' => collect($report['records'])->map(fn ($r) => [
                    $r['user_name'] ?? '—',
                    $r['department'] ?? '—',
                    $time($r['check_in']),
                    $time($r['check_out']),
                    $r['duration'] !== null ? intdiv($r['duration'], 60) . 'h ' . ($r['duration'] % 60) . 'm' : '—',
                    ucfirst($r['status']),
                ])->values()->all(),
            ];
        }

        if ($month || $year) {
            $report = $this->monthly($user, $month, $year);
            $label = Carbon::create($report['year'], $report['month'], 1)->format('F Y');

            return [
                'title' => 'Monthly Attendance — ' . $label,
                'filename' => 'attendance-monthly-' . $report['year'] . '-' . str_pad((string) $report['month'], 2, '0', STR_PAD_LEFT),
                'headings' => ['Employee', 'Total Days', 'Present', 'Absent', 'On Leave', 'Late'],
                'rows' => collect($report['records'])->map(fn ($r) => [
                    $r['user_name'] ?? '—',
                    $r['total_days'],
                    $r['present_days'],
                    $r['absent_days'],
                    $r['leave_days'],
                    $r['late_days'],
                ])->values()->all(),
            ];
        }

        $summary = $this->summary($user);

        return [
            'title' => 'Attendance Summary',
            'filename' => 'attendance-summary',
            'headings' => ['Metric', 'Count'],
            'rows' => [
                ['Total Employees', $summary['total_employees']],
                ['Present Today', $summary['present_today']],
                ['On Leave Today', $summary['on_leave_today']],
                ['Absent Today', $summary['absent_today']],
                ['Total Late (all time)', $summary['total_late']],
                ['Total Absent (all time)', $summary['total_absent']],
                ['Total Early Leave (all time)', $summary['total_early']],
            ],
        ];
    }

    public function exportPdf($user, array $filters = [])
    {
        $payload = $this->exportPayload($user, $filters);

        $pdf = PDF::loadView('reports.export', $payload);

        return $pdf->download($payload['filename'] . '.pdf');
    }

    public function exportExcel($user, array $filters = [])
    {
        $payload = $this->exportPayload($user, $filters);

        return Excel::download(
            new AttendanceExport($payload['headings'], $payload['rows']),
            $payload['filename'] . '.xlsx'
        );
    }
}
