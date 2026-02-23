<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditController extends Controller
{
    public function index()
    {
        return AuditLog::with('user')
            ->latest()
            ->paginate(50);
    }

}
