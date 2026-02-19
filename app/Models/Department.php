<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "work_start",
        "work_end",
        "late_after",
        "early_leave_before"
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
