<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var User $account */
        $account = $this->route('user');
        $isSuperAdmin = $account->hasRole(Role::SUPER_ADMIN);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($account)],
            'roles' => $isSuperAdmin ? ['prohibited'] : ['required', 'array', 'min:1', 'max:2'],
            'roles.*' => $isSuperAdmin ? ['prohibited'] : [
                'required',
                'string',
                'distinct',
                Rule::exists('roles', 'slug')->where(fn ($query) => $query->whereIn('slug', Role::assignableSlugs())),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var User $account */
            $account = $this->route('user');

            if ($account->hasRole(Role::SUPER_ADMIN) && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Super Admin accounts cannot be deactivated here.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
