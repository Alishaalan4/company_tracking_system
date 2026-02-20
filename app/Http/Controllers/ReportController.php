<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

class ReportController extends Controller
{
     protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function daily(Request $request)
    {
        return $this->reportService->daily($request->user(), $request->date);
    }

    public function monthly(Request $request)
    {
        return $this->reportService->monthly($request->user(), $request->month);
    }

    public function summary(Request $request)
    {
        return $this->reportService->summary($request->user());
    }

    public function exportPdf(Request $request)
    {
        return $this->reportService->exportPdf($request->user());
    }

    public function exportExcel(Request $request)
    {
        return $this->reportService->exportExcel($request->user());
    }
}
