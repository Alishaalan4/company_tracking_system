<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'work_start' => 'required',
            'work_end' => 'required',
            'late_after' => 'required|integer',
            'early_leave_before' => 'required|integer',
        ]);

        return Department::create($request->all());
    }

    public function show(Department $department)
    {
        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $department->update($request->all());
        return $department;
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
