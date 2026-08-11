<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function log($userId, $action, $description = null, $meta = [])
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            // `ip` is surfaced by AuditLogResource as ip_address.
            'meta' => array_merge(['ip' => request()?->ip()], $meta),
        ]);
    }

    /**
     * Convenience entry point for controllers: attributes the entry to the
     * current user and derives model/model_id from the affected record.
     *
     * Auditing must never break the request that triggered it, so failures
     * here are swallowed.
     */
    public static function record(
        string $action,
        ?string $description = null,
        ?Model $model = null,
        array $changes = []
    ): void {
        try {
            $meta = [];

            if ($model) {
                $meta['model'] = class_basename($model);
                $meta['model_id'] = $model->getKey();
            }

            if ($changes) {
                $meta['changes'] = $changes;
            }

            app(self::class)->log(
                optional(request()?->user())->id,
                $action,
                $description,
                $meta
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
