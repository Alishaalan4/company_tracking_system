<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Authenticatable implements CanResetPasswordContract
{
    // The users table has a deleted_at column, but without this trait
    // "delete user" was a hard delete and the column went unused.
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pin',
        'role_id',
        'department_id',
        'is_active',
        'must_change_password',
        'must_change_pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
        'must_change_pin' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Role names are compared case-insensitively. Existing rows are stored
     * capitalised ("Admin"), while the seeder and the SPA use lowercase;
     * a strict === here silently made every role check return false.
     */
    public function hasRole(string $role): bool
    {
        return strcasecmp((string) optional($this->role)->name, $role) === 0;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
    public function isManager()
    {
        return $this->hasRole('manager');
    }
    public function isEmployee()
    {
        return $this->hasRole('employee');
    }

}
