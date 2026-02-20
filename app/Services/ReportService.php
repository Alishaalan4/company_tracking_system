<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
class ReportService
{
    public function daily($user, $date)
    {
        $date = Carbon::parse($date);

        $query = Attendance::whereDate($date);

        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        return $query->with('user')->get();
    }

    public function monthly($user, $month)
    {
        $month = Carbon::parse($month);

        $query = Attendance::whereMonth('date', $month->month)
            ->whereYear('date', $month->year);

        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        return $query->get();
    }

    public function summary($user)
    {
        $query = Attendance::query();

        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        return [
            'total_late' => $query->where('is_late', true)->count(),
            'total_absent' => $query->where('is_absent', true)->count(),
            'total_early' => $query->where('left_early', true)->count(),
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