<?php

namespace App\Http\Requests;

use App\HolidayDayPortion;
use App\HolidayType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('holiday')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'holiday_date' => ['required', 'date'],
            'type' => ['required', Rule::enum(HolidayType::class)],
            'day_portion' => ['required', Rule::enum(HolidayDayPortion::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
