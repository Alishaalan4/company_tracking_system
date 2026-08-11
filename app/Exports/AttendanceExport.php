<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Receives pre-built rows from ReportService so the spreadsheet reflects the
 * selected report and filters. It used to dump `Attendance::all()` raw,
 * ignoring both the filters and the manager department scope.
 */
class AttendanceExport implements FromCollection, WithHeadings
{
    public function __construct(
        private array $headings = [],
        private array $rows = []
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
