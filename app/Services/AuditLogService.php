<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function log($userId, $action, $description = null, $meta = [])
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'meta' => $meta
        ]);
    }
}