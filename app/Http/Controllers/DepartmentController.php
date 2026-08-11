<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditLogService;
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

        $department = Department::create($request->all());

        AuditLogService::record('department.created', "Created department {$department->name}", $department);

        return $department;
    }

    public function show(Department $department)
    {
        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $before = $department->only(['name','work_start','work_end','late_after','early_leave_before']);
        $department->update($request->all());

        AuditLogService::record(
            'department.updated',
            "Updated department {$department->name}",
            $department,
            ['before' => $before, 'after' => $department->only(array_keys($before))]
        );

        return $department;
    }

    public function destroy(Department $department)
    {
        AuditLogService::record('department.deleted', "Deleted department {$department->name}", $department);

        $department->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
