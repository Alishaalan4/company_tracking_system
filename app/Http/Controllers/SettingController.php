<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Settings live as key/value rows, but the SPA edits one flat object.
     * Defaults keep the form populated before anything has been saved.
     */
    private const DEFAULTS = [
        'company_name' => '',
        'working_hours_start' => '09:00',
        'working_hours_end' => '17:00',
        'late_threshold_minutes' => 15,
        'timezone' => 'UTC',
        'allow_remote_work' => false,
    ];

    public function index()
    {
        $stored = Setting::pluck('value', 'key');

        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = $stored->has($key)
                ? $this->cast($stored[$key], $default)
                : $default;
        }

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'sometimes|nullable|string|max:255',
            'working_hours_start' => 'sometimes|nullable|string|max:10',
            'working_hours_end' => 'sometimes|nullable|string|max:10',
            'late_threshold_minutes' => 'sometimes|nullable|integer|min:0',
            'timezone' => 'sometimes|nullable|string|max:64',
            'allow_remote_work' => 'sometimes|boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]
            );
        }

        AuditLogService::record(
            'settings.updated',
            'Updated application settings',
            null,
            ['fields' => array_keys($validated)]
        );

        return $this->index();
    }

    /**
     * Values are stored as text; coerce back to the default's type.
     */
    private function cast($value, $default)
    {
        if (is_bool($default)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_int($default)) {
            return (int) $value;
        }

        return (string) $value;
    }
}
