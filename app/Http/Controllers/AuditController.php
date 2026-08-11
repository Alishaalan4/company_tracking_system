<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Http\Resources\AuditLogResource;

class AuditController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')
            ->latest()
            ->paginate(50);

        return AuditLogResource::collection($logs);
    }

}
