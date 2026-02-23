<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        ]);

        return NonWorkingDay::create($request->all());
    }

    public function update(Request $request, NonWorkingDay $nonWorkingDay)
    {
        $request->validate([
            'date' => 'required|date|unique:non_working_days,date,' . $nonWorkingDay->id,
        ]);

        $nonWorkingDay->update($request->all());

        return $nonWorkingDay;
    }

    public function destroy(NonWorkingDay $nonWorkingDay)
    {
        $nonWorkingDay->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
