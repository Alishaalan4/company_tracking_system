<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return LeaveType::create($request->all());
    }

    public function show(LeaveType $leaveType)
    {
        return $leaveType;
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $leaveType->update($request->all());
        return $leaveType;
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
