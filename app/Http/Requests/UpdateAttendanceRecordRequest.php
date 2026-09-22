<?php

namespace App\Http\Requests;

use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('attendance_record')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $attendanceRecord = $this->route('attendance_record');

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(function ($query) use ($attendanceRecord): void {
                    $query->whereNull('deleted_at');
                    if ($attendanceRecord instanceof AttendanceRecord) {
                        $query->orWhere('id', $attendanceRecord->employee_id);
                    }
                }),
            ],
            'attendance_date' => [
                'required',
                'date',
            ],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'check_in_at' => ['nullable', 'required_with:check_out_at', 'date_format:Y-m-d\TH:i'],
            'check_out_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:check_in_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $attendanceRecord = $this->route('attendance_record');
            if (! $validator->errors()->hasAny(['employee_id', 'attendance_date'])
                && AttendanceRecord::query()
                    ->where('employee_id', $this->integer('employee_id'))
                    ->whereDate('attendance_date', $this->string('attendance_date')->toString())
                    ->when($attendanceRecord instanceof AttendanceRecord, fn ($query) => $query->whereKeyNot($attendanceRecord->id))
                    ->exists()) {
                $validator->errors()->add('attendance_date', 'An attendance record already exists for this employee and date.');
            }

            if ($validator->errors()->has('status')) {
                return;
            }

            $status = AttendanceStatus::tryFrom($this->string('status')->toString());
            if (in_array($status, [AttendanceStatus::Absent, AttendanceStatus::Leave], true)
                && ($this->filled('check_in_at') || $this->filled('check_out_at'))) {
                $validator->errors()->add('check_in_at', 'Check-in and check-out times must be empty for absent or leave records.');
            }
        }];
    }
}
