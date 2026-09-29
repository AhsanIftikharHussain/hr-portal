<x-layouts.admin title="Add User">
    <div class="flex flex-col gap-6">
        <x-page-header title="Add User" description="Create an internally provisioned HR Admin or Employee account." />
        <x-card>
            <form method="POST" action="{{ route('admin.users.store') }}" class="flex flex-col gap-6">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form-input label="Name" name="name" maxlength="255" required autofocus />
                    <x-form-input label="Email address" name="email" type="email" autocomplete="email" maxlength="255" required />
                    <x-form-input label="Password" name="password" type="password" autocomplete="new-password" required />
                    <x-form-input label="Confirm password" name="password_confirmation" type="password" autocomplete="new-password" required />
                </div>
                @include('admin.users._roles', ['selectedRoles' => []])
                <x-form-select label="Account status" name="is_active" :options="['1' => 'Active', '0' => 'Inactive']" value="1" required />
                <p class="text-xs text-slate-500">Passwords must be at least 12 characters and include uppercase, lowercase, numbers, and symbols.</p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                    <x-button>Create User</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
