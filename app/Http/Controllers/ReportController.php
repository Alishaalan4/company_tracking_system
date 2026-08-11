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
        return $this->reportService->monthly(
            $request->user(),
            $request->query('month'),
            $request->query('year')
        );
    }

    public function summary(Request $request)
    {
        return $this->reportService->summary($request->user());
    }

    public function exportPdf(Request $request)
    {
        return $this->reportService->exportPdf($request->user(), $this->filters($request));
    }

    public function exportExcel(Request $request)
    {
        return $this->reportService->exportExcel($request->user(), $this->filters($request));
    }

    /**
     * Export filters mirror the report tabs: a date gives the daily report,
     * month/year the monthly one, and neither falls back to the summary.
     */
    private function filters(Request $request): array
    {
        return array_filter([
            'date' => $request->query('date'),
            'month' => $request->query('month'),
            'year' => $request->query('year'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
