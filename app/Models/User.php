<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function reviewedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'reviewed_by');
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function recordedAttendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function correctedAttendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'corrected_by');
    }

    /** @return HasMany<EmployeeContract, $this> */
    public function uploadedContracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class, 'uploaded_by');
    }

    /** @return HasMany<EmployeeDocument, $this> */
    public function uploadedEmployeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'uploaded_by');
    }

    /** @return HasMany<Policy, $this> */
    public function createdPolicies(): HasMany
    {
        return $this->hasMany(Policy::class, 'created_by');
    }

    /** @return HasMany<Policy, $this> */
    public function updatedPolicies(): HasMany
    {
        return $this->hasMany(Policy::class, 'updated_by');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
