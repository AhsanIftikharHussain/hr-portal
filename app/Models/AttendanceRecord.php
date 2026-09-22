<?php

namespace App\Models;

use App\AttendanceSource;
use App\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'attendance_date', 'check_in_at', 'check_out_at', 'status', 'source', 'original_source',
    'notes', 'recorded_by', 'corrected_by', 'corrected_at',
])]
class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function workingDurationMinutes(): ?int
    {
        if ($this->check_in_at === null || $this->check_out_at === null) {
            return null;
        }

        return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
            'source' => AttendanceSource::class,
            'original_source' => AttendanceSource::class,
            'corrected_at' => 'datetime',
        ];
    }
}
