<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "date",
        "check_in_at",
        "check_out_at",
        "is_late",
        "left_early",
        "is_absent"
    ];
    protected $casts = [
        "date"=>"date",
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'is_late' => 'boolean',
        'left_early' => 'boolean',
        'is_absent' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
