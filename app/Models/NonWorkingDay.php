<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonWorkingDay extends Model
{
    use HasFactory;
        protected $fillable = [
        'date',
        'name',
        'reason',
        'is_recurring'
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * A recurring entry matches on month/day regardless of year.
     */
    public static function fallsOn($date): bool
    {
        $date = \Carbon\Carbon::parse($date);

        return static::query()
            ->where(function ($q) use ($date) {
                $q->where('is_recurring', false)
                    ->whereDate('date', $date->toDateString());
            })
            ->orWhere(function ($q) use ($date) {
                $q->where('is_recurring', true)
                    ->whereMonth('date', $date->month)
                    ->whereDay('date', $date->day);
            })
            ->exists();
    }
}
