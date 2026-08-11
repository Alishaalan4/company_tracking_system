<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Models\NonWorkingDay;

class NonWorkingDayController extends Controller
{
    public function index()
    {
        return NonWorkingDay::orderBy('date')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:non_working_days,date',
            'name' => 'nullable|string',
            'reason' => 'nullable|string',
            'is_recurring' => 'sometimes|boolean',
        ]);

        $day = NonWorkingDay::create($request->all());

        AuditLogService::record('non_working_day.created', "Added non-working day {$day->name}", $day);

        return $day;
    }

    public function update(Request $request, NonWorkingDay $nonWorkingDay)
    {
        $request->validate([
            'date' => 'required|date|unique:non_working_days,date,' . $nonWorkingDay->id,
            'name' => 'nullable|string',
            'reason' => 'nullable|string',
            'is_recurring' => 'sometimes|boolean',
        ]);

        $nonWorkingDay->update($request->all());

        AuditLogService::record('non_working_day.updated', "Updated non-working day {$nonWorkingDay->name}", $nonWorkingDay);

        return $nonWorkingDay;
    }

    public function destroy(NonWorkingDay $nonWorkingDay)
    {
        AuditLogService::record('non_working_day.deleted', "Removed non-working day {$nonWorkingDay->name}", $nonWorkingDay);

        $nonWorkingDay->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
