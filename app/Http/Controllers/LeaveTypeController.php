<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Models\LeaveType;
class LeaveTypeController extends Controller
{
    public function index()
    {
        return LeaveType::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'annual_limit' => 'nullable|integer'
        ]);

        $leaveType = LeaveType::create($request->all());

        AuditLogService::record('leave_type.created', "Created leave type {$leaveType->name}", $leaveType);

        return $leaveType;
    }

    public function show(LeaveType $leaveType)
    {
        return $leaveType;
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $leaveType->update($request->all());

        AuditLogService::record('leave_type.updated', "Updated leave type {$leaveType->name}", $leaveType);

        return $leaveType;
    }

    public function destroy(LeaveType $leaveType)
    {
        AuditLogService::record('leave_type.deleted', "Deleted leave type {$leaveType->name}", $leaveType);

        $leaveType->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
