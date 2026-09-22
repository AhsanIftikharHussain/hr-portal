<?php

namespace App\Http\Requests;

use App\Models\Policy;
use App\PolicyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('policy')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $policy = $this->route('policy');

        return [
            'policy_category_id' => [
                'required',
                'integer',
                Rule::exists('policy_categories', 'id')->where(function ($query) use ($policy): void {
                    $query->where('is_active', true);
                    if ($policy instanceof Policy) {
                        $query->orWhere('id', $policy->policy_category_id);
                    }
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::enum(PolicyStatus::class)],
            'effective_date' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->string('status')->toString() === PolicyStatus::Published->value
                    || ($policy instanceof Policy && $policy->status === PolicyStatus::Published)),
                'date',
            ],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $policy = $this->route('policy');
            if (! $policy instanceof Policy || $validator->errors()->has('status')) {
                return;
            }

            $requestedStatus = PolicyStatus::tryFrom($this->string('status')->toString());
            if ($requestedStatus === null || ! $policy->status->canTransitionTo($requestedStatus)) {
                $validator->errors()->add('status', 'This policy status transition is not allowed.');
            }

            if ($policy->status === PolicyStatus::Archived) {
                $validator->errors()->add('status', 'Archived policies cannot be changed.');
            }
        }];
    }
}
