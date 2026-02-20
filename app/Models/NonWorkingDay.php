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
        'reason'
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
