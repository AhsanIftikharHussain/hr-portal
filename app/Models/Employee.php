<?php

namespace App\Models;

use App\EmploymentStatus;
use App\EmploymentType;
use App\Gender;
use App\MaritalStatus;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'employee_code', 'full_name', 'national_id', 'date_of_birth', 'gender', 'marital_status',
    'personal_email', 'official_email', 'contact_number', 'alternate_contact_number', 'address', 'city',
    'department_id', 'job_title_id', 'reporting_manager_id', 'user_id', 'employment_type',
    'employment_status', 'joining_date', 'confirmation_date', 'work_location',
    'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_number',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id')->withTrashed();
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<EmployeeContract, $this> */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    /** @return HasMany<EmployeeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'confirmation_date' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'employment_type' => EmploymentType::class,
            'employment_status' => EmploymentStatus::class,
        ];
    }
}
